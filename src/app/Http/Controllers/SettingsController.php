<?php

namespace App\Http\Controllers;

use App\Services\AiAdvisor;
use App\Services\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit(Request $request, AiAdvisor $advisor)
    {
        return view('settings', [
            'user'      => $request->user(),
            'providers' => $advisor->availableProviders(),
            'usdRate'   => ExchangeRate::for($request->user()),
        ]);
    }

    public function update(Request $request, AiAdvisor $advisor)
    {
        $data = $request->validate([
            'ai_provider'     => ['nullable', Rule::in(array_keys($advisor->availableProviders()))],
            'usd_rate_type'   => ['required', Rule::in(array_keys(ExchangeRate::TYPES))],
            'usd_rate'        => ['nullable', 'required_if:usd_rate_type,manual', 'numeric', 'min:0.0001', 'max:99999999'],
        ], [
            'usd_rate.required_if' => 'Cargá la cotización del dólar si elegís el tipo manual.',
        ]);

        $user = $request->user();
        $typeChanged = $user->usd_rate_type !== $data['usd_rate_type'];

        if ($data['usd_rate_type'] === 'manual') {
            $data['usd_rate_updated_at'] = now();
        } else {
            unset($data['usd_rate']); // la cotización viene de la API
        }

        $user->update($data);

        if ($typeChanged && $user->usd_rate_type !== 'manual' && ! ExchangeRate::refresh($user)) {
            return back()->with('error', 'Ajustes guardados, pero no se pudo obtener la cotización del dólar en este momento. Se va a reintentar automáticamente.');
        }

        return back()->with('success', 'Ajustes guardados.');
    }

    public function refreshRate(Request $request)
    {
        $ok = ExchangeRate::refresh($request->user());

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'Cotización del dólar actualizada.' : 'No se pudo obtener la cotización en este momento. Probá más tarde.'
        );
    }
}
