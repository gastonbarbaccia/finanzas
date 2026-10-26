<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiRecommendation;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Conecta la app con Gemini, OpenAI o Claude.
 * - recommend(): informe completo de una sola vez.
 * - chat(): conversación con historial.
 * En ambos casos se envían solo datos agregados (sin nombre, email ni descripciones).
 */
class AiAdvisor
{
    /** Cantidad de mensajes previos que se envían como contexto en el chat. */
    private const CHAT_HISTORY_LIMIT = 20;

    private const REPORT_PROMPT = <<<'PROMPT'
Sos un asesor de finanzas personales. Recibís un JSON con datos agregados de gastos y deudas de una persona.
Respondé en español, en Markdown, con tono directo y práctico, usando estas secciones:

## Diagnóstico
Resumen en 3-4 líneas de la situación actual.

## Alertas
Problemas concretos detectados (categorías sobre presupuesto, gasto en aumento, gastos pendientes o vencidos, deuda cara, ratio deuda/ingreso alto). Si no hay, decilo.

## Recomendaciones
Entre 4 y 6 acciones concretas, cada una con un monto o porcentaje estimado de ahorro basado en los datos.

## Estrategia de deudas
Si hay deudas, indicá en qué orden pagarlas (método avalancha por tasa vs. bola de nieve por saldo) y por qué. Si no hay, sugerí cómo evitar endeudarse.

## Próximos 30 días
Tres pasos simples para el próximo mes.

Reglas: basate solo en los datos recibidos, no inventes cifras. Si falta un dato clave (por ejemplo el ingreso mensual), mencionalo y sugerí cargarlo.
Montos en la misma moneda de los datos. Cerrá con una línea aclarando que es orientación general y no asesoramiento financiero profesional.
PROMPT;

    private const CHAT_PROMPT = <<<'PROMPT'
Sos el asesor de finanzas personales integrado en la app "Mis Finanzas". Conversás con el usuario sobre sus gastos, deudas, presupuestos y ahorro.
Al final de estas instrucciones tenés un JSON con los datos actuales del usuario, agregados por la app (se actualizan en cada mensaje).

Cómo responder:
- En español rioplatense, cercano y directo. Respuestas breves por defecto (2 a 6 párrafos cortos o una lista); extendete solo si te lo piden.
- Usá Markdown simple: negritas, listas y tablas chicas cuando ayuden.
- Cuando la pregunta sea sobre su situación, apoyate en los números del JSON y citá montos concretos. No inventes datos que no estén.
- Si falta información para responder bien (por ejemplo el ingreso mensual o un presupuesto), decilo y sugerí cargarlo en la app.
- Podés responder preguntas generales de finanzas personales, pero no des recomendaciones de inversión específicas (acciones, criptomonedas, productos financieros puntuales).
- Si te preguntan algo que no tiene que ver con finanzas personales, respondé brevemente y volvé a ofrecer ayuda con sus finanzas.
- Cuando des recomendaciones importantes, aclarás en una línea que es orientación general y no asesoramiento profesional (no lo repitas en cada mensaje).
PROMPT;

    public function availableProviders(): array
    {
        return collect(config('ai.providers'))
            ->filter(fn ($p) => ! empty($p['key']))
            ->map(fn ($p) => $p['label'] . ' (' . $p['model'] . ')')
            ->all();
    }

    /** Informe completo (sección "Informes"). */
    public function recommend(User $user, string $provider): AiRecommendation
    {
        $snapshot = (new FinanceMetrics($user))->aiSnapshot();
        $prompt = "Analizá estos datos financieros y dame recomendaciones:\n\n"
            . json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $content = $this->complete($provider, self::REPORT_PROMPT, [['role' => 'user', 'content' => $prompt]], 2500);

        return $user->aiRecommendations()->create([
            'provider' => $provider,
            'model'    => config("ai.providers.$provider.model"),
            'content'  => $content,
        ]);
    }

    /**
     * Envía un mensaje dentro de una conversación y guarda la pregunta y la respuesta.
     * Si la IA falla no se guarda nada y se lanza RuntimeException.
     */
    public function chat(User $user, AiConversation $conversation, string $provider, string $message): AiMessage
    {
        $history = $conversation->messages()
            ->reorder('id', 'desc')->take(self::CHAT_HISTORY_LIMIT)->get()
            ->reverse()->values()
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content]);

        // Todos los proveedores exigen que la conversación empiece con el usuario
        while ($history->isNotEmpty() && $history->first()['role'] !== 'user') {
            $history->shift();
        }

        $messages = $history->push(['role' => 'user', 'content' => $message])->all();

        $snapshot = (new FinanceMetrics($user))->aiSnapshot();
        $system = self::CHAT_PROMPT . "\n\nDatos financieros actuales del usuario (JSON):\n"
            . json_encode($snapshot, JSON_UNESCAPED_UNICODE);

        $reply = $this->complete($provider, $system, $messages, 1500);

        return DB::transaction(function () use ($conversation, $provider, $message, $reply) {
            $conversation->messages()->create(['role' => 'user', 'content' => $message]);
            $answer = $conversation->messages()->create([
                'role'     => 'assistant',
                'content'  => $reply,
                'provider' => $provider,
                'model'    => config("ai.providers.$provider.model"),
            ]);
            $conversation->update(['provider' => $provider]);
            $conversation->touch();

            return $answer;
        });
    }

    /**
     * Llamada genérica al proveedor.
     *
     * @param  array<int, array{role: string, content: string}>  $messages  roles: user | assistant
     */
    public function complete(string $provider, string $system, array $messages, int $maxTokens = 2000): string
    {
        $config = config("ai.providers.$provider");
        if (! $config || empty($config['key'])) {
            throw new RuntimeException('El proveedor de IA elegido no está configurado.');
        }

        try {
            $content = match ($provider) {
                'gemini' => $this->callGemini($config, $system, $messages, $maxTokens),
                'openai' => $this->callOpenAi($config, $system, $messages, $maxTokens),
                'claude' => $this->callClaude($config, $system, $messages, $maxTokens),
            };
        } catch (RequestException $e) {
            // Se registra el código y el cuerpo de error, nunca la API key.
            Log::warning('Error del proveedor de IA', [
                'provider' => $provider,
                'status'   => $e->response?->status(),
                'body'     => mb_substr((string) $e->response?->body(), 0, 500),
            ]);
            throw new RuntimeException($this->friendlyError($e->response?->status()));
        } catch (ConnectionException $e) {
            Log::warning('Sin conexión con el proveedor de IA', ['provider' => $provider]);
            throw new RuntimeException('No se pudo conectar con el proveedor de IA. Probá de nuevo en unos minutos.');
        }

        if (trim((string) $content) === '') {
            throw new RuntimeException('La IA devolvió una respuesta vacía. Probá de nuevo.');
        }

        return trim($content);
    }

    private function http()
    {
        return Http::timeout(config('ai.timeout', 60))->acceptJson()->asJson();
    }

    private function callGemini(array $config, string $system, array $messages, int $maxTokens): string
    {
        $url = rtrim($config['url'], '/') . '/models/' . rawurlencode($config['model']) . ':generateContent';

        $response = $this->http()
            ->withHeaders(['x-goog-api-key' => $config['key']])
            ->post($url, [
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'contents'          => array_map(fn ($m) => [
                    'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $m['content']]],
                ], $messages),
                // Sin maxOutputTokens: en los modelos con razonamiento el límite incluye
                // los tokens de "pensamiento" y puede cortar la respuesta.
                'generationConfig'  => ['temperature' => 0.4],
            ])->throw();

        return collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')->filter()->implode("\n");
    }

    private function callOpenAi(array $config, string $system, array $messages, int $maxTokens): string
    {
        $response = $this->http()
            ->withToken($config['key'])
            ->post(rtrim($config['url'], '/') . '/chat/completions', [
                'model'                 => $config['model'],
                'max_completion_tokens' => $maxTokens,
                'messages'              => array_merge([['role' => 'system', 'content' => $system]], $messages),
            ])->throw();

        return (string) $response->json('choices.0.message.content', '');
    }

    private function callClaude(array $config, string $system, array $messages, int $maxTokens): string
    {
        $response = $this->http()
            ->withHeaders([
                'x-api-key'         => $config['key'],
                'anthropic-version' => '2023-06-01',
            ])
            ->post(rtrim($config['url'], '/') . '/messages', [
                'model'      => $config['model'],
                'max_tokens' => $maxTokens,
                'system'     => $system,
                'messages'   => $messages,
            ])->throw();

        return collect($response->json('content', []))
            ->where('type', 'text')->pluck('text')->implode("\n");
    }

    private function friendlyError(?int $status): string
    {
        return match (true) {
            in_array($status, [401, 403]) => 'La API key del proveedor de IA es inválida o no tiene permisos.',
            $status === 404               => 'El modelo configurado no existe. Revisá el nombre del modelo en el .env.',
            $status === 429               => 'Se alcanzó el límite de uso del proveedor de IA. Esperá un momento.',
            default                       => 'El proveedor de IA devolvió un error. Probá de nuevo más tarde.',
        };
    }
}
