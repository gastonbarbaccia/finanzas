<?php

namespace App\Http\Controllers;

use App\Models\AiRecommendation;
use App\Services\AiAdvisor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class AiController extends Controller
{
    public function index(Request $request, AiAdvisor $advisor)
    {
        $providers = $advisor->availableProviders();
        $preferred = $request->user()->ai_provider;

        return view('ai.reports', [
            'providers'       => $providers,
            'selected'        => array_key_exists($preferred, $providers) ? $preferred : config('ai.default'),
            'recommendations' => $request->user()->aiRecommendations()->paginate(5),
        ]);
    }

    public function generate(Request $request, AiAdvisor $advisor)
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys($advisor->availableProviders()))],
        ]);

        if (! $request->user()->expenses()->exists() && ! $request->user()->debts()->exists()) {
            return back()->with('error', 'Cargá algunos gastos o deudas antes de pedir recomendaciones.');
        }

        try {
            $advisor->recommend($request->user(), $data['provider']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('ai.reports')->with('success', 'Nuevo informe generado.');
    }

    public function destroy(Request $request, AiRecommendation $recommendation)
    {
        abort_unless($recommendation->user_id === $request->user()->id, 404);
        $recommendation->delete();

        return back()->with('success', 'Informe eliminado.');
    }
}
