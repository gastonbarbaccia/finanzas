@extends('layouts.app', ['title' => 'Ajustes'])
@section('content')
<h1 class="mb-6 font-display text-3xl font-bold">Ajustes</h1>

<form method="POST" action="{{ route('settings.update') }}" class="panel max-w-xl space-y-5 p-6">
  @csrf @method('PUT')
  <p class="rounded-lg bg-paper px-4 py-3 text-sm">Tus ingresos mensuales ahora se cargan en <a class="font-semibold text-pine hover:underline" href="{{ route('incomes.index') }}">Ingresos</a>, donde podés tener varios, en pesos o en dólares.</p>

  <div>
    <label class="label" for="ai_provider">Proveedor de IA preferido</label>
    <select class="field" id="ai_provider" name="ai_provider" @disabled(empty($providers))>
      <option value="">Usar el predeterminado</option>
      @foreach ($providers as $key => $label)
        <option value="{{ $key }}" @selected(old('ai_provider', $user->ai_provider) === $key)>{{ $label }}</option>
      @endforeach
    </select>
    @if (empty($providers))
      <p class="mt-1 text-xs text-muted">No hay proveedores configurados en el <code>.env</code>.</p>
    @endif
  </div>

  <fieldset class="space-y-3 border-t border-line pt-5">
    <legend class="font-display text-lg font-bold">Dólar</legend>
    <p class="text-sm text-muted">Se usa para convertir a pesos los gastos y deudas en dólares en los totales, gráficos y reportes. Cada gasto guarda la cotización del día en que lo cargaste.</p>
    <div>
      <label class="label" for="usd_rate_type">Cotización a usar</label>
      <select class="field" id="usd_rate_type" name="usd_rate_type">
        @foreach (\App\Services\ExchangeRate::TYPES as $key => $label)
          <option value="{{ $key }}" @selected(old('usd_rate_type', $user->usd_rate_type) === $key)>{{ $label }}</option>
        @endforeach
      </select>
      <p class="mt-1 text-xs text-muted">Salvo la manual, se actualiza sola cada hora con el valor de venta publicado por DolarApi.com.</p>
    </div>
    <div id="manual-rate" class="{{ old('usd_rate_type', $user->usd_rate_type) === 'manual' ? '' : 'hidden' }}">
      <label class="label" for="usd_rate">Pesos por dólar</label>
      <input class="field num" id="usd_rate" name="usd_rate" type="number" step="0.0001" min="0.0001" value="{{ old('usd_rate', $user->usd_rate) }}">
    </div>
    <p class="text-sm">
      @if ($usdRate)
        Cotización actual: <strong class="num">@money($usdRate)</strong>
        @if ($user->usd_rate_updated_at)<span class="text-muted">, actualizada {{ $user->usd_rate_updated_at->diffForHumans() }}</span>@endif
      @else
        <span class="text-amber">Todavía no hay cotización.</span>
      @endif
    </p>
  </fieldset>

  <button class="btn-primary">Guardar ajustes</button>
</form>
@if ($user->usd_rate_type !== 'manual')
  <form method="POST" action="{{ route('settings.usd-rate') }}" class="mt-3 max-w-xl">
    @csrf
    <button class="btn-ghost">Actualizar cotización ahora</button>
  </form>
@endif
@endsection

@push('scripts')
<script>
document.getElementById('usd_rate_type')?.addEventListener('change', e =>
  document.getElementById('manual-rate').classList.toggle('hidden', e.target.value !== 'manual'));
</script>
@endpush
