<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\AiAdvisor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class AiChatController extends Controller
{
    public function index(Request $request, AiAdvisor $advisor)
    {
        return $this->render($request, $advisor, null);
    }

    public function show(Request $request, AiAdvisor $advisor, AiConversation $conversation)
    {
        $this->authorizeOwner($request, $conversation);

        return $this->render($request, $advisor, $conversation);
    }

    /** Primer mensaje: crea la conversación. */
    public function store(Request $request, AiAdvisor $advisor)
    {
        $data = $this->validated($request, $advisor);

        $conversation = $request->user()->aiConversations()->create([
            'title'    => Str::limit(preg_replace('/\s+/', ' ', $data['message']), 60),
            'provider' => $data['provider'],
        ]);

        try {
            $advisor->chat($request->user(), $conversation, $data['provider'], $data['message']);
        } catch (RuntimeException $e) {
            $conversation->delete();

            return $this->fail($request, $e->getMessage());
        }

        $url = route('ai.chat.show', $conversation);

        return $request->expectsJson() ? response()->json(['redirect' => $url]) : redirect($url);
    }

    /** Mensajes siguientes dentro de una conversación. */
    public function message(Request $request, AiAdvisor $advisor, AiConversation $conversation)
    {
        $this->authorizeOwner($request, $conversation);
        $data = $this->validated($request, $advisor);

        try {
            $answer = $advisor->chat($request->user(), $conversation, $data['provider'], $data['message']);
        } catch (RuntimeException $e) {
            return $this->fail($request, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('ai.partials.message', ['message' => $answer])->render(),
            ]);
        }

        return redirect()->route('ai.chat.show', $conversation);
    }

    public function destroy(Request $request, AiConversation $conversation)
    {
        $this->authorizeOwner($request, $conversation);
        $conversation->delete();

        return redirect()->route('ai.index')->with('success', 'Conversación eliminada.');
    }

    private function render(Request $request, AiAdvisor $advisor, ?AiConversation $conversation)
    {
        $providers = $advisor->availableProviders();
        $preferred = $conversation?->provider ?? $request->user()->ai_provider;

        return view('ai.chat', [
            'conversations' => $request->user()->aiConversations()->take(50)->get(),
            'conversation'  => $conversation,
            'messages'      => $conversation?->messages()->get() ?? collect(),
            'providers'     => $providers,
            'selected'      => array_key_exists($preferred ?? '', $providers) ? $preferred : config('ai.default'),
        ]);
    }

    private function validated(Request $request, AiAdvisor $advisor): array
    {
        return $request->validate([
            'message'  => ['required', 'string', 'max:2000'],
            'provider' => ['required', Rule::in(array_keys($advisor->availableProviders()))],
        ], [
            'provider.in' => 'El proveedor de IA elegido no está configurado.',
        ]);
    }

    private function fail(Request $request, string $error)
    {
        return $request->expectsJson()
            ? response()->json(['error' => $error], 422)
            : back()->withInput()->with('error', $error);
    }

    private function authorizeOwner(Request $request, AiConversation $conversation): void
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
    }
}
