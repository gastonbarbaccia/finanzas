@extends('layouts.app', ['title' => 'Reportes'])
@section('content')
<h1 class="font-display text-3xl font-bold">Reportes en PDF</h1>
<p class="mt-1 max-w-2xl text-muted">Elegí el período y qué secciones incluir. El PDF trae un resumen, el gasto por categoría y por mes, los medios de pago y el estado de tus deudas.</p>

<form method="GET" action="{{ route('reports.download') }}" class="panel mt-6 max-w-2xl space-y-6 p-6" id="report-form">
  <fieldset>
    <legend class="label">Período</legend>
    <div class="mt-1 grid gap-2 sm:grid-cols-3">
      @foreach ($periods as $key => $label)
        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm has-[:checked]:border-pine has-[:checked]:bg-pine-soft">
          <input type="radio" name="period" value="{{ $key }}" class="text-pine focus:ring-pine" @checked(old('period', 'this_month') === $key)>
          {{ $label }}
        </label>
      @endforeach
    </div>
    <div id="custom-range" class="mt-3 grid gap-3 sm:grid-cols-2 {{ old('period') === 'custom' ? '' : 'hidden' }}">
      <div>
        <label class="label" for="from">Desde</label>
        <input class="field" type="date" id="from" name="from" value="{{ old('from', now()->startOfMonth()->format('Y-m-d')) }}">
      </div>
      <div>
        <label class="label" for="to">Hasta</label>
        <input class="field" type="date" id="to" name="to" value="{{ old('to', now()->format('Y-m-d')) }}">
      </div>
    </div>
  </fieldset>

  <fieldset class="space-y-2">
    <legend class="label">Incluir también</legend>
    <label class="flex items-start gap-3 text-sm">
      <input type="checkbox" name="details" value="1" checked class="mt-0.5 rounded border-line text-pine focus:ring-pine">
      <span><span class="font-medium">Detalle de gastos</span><span class="block text-muted">La lista completa de gastos del período, con fecha, categoría y estado.</span></span>
    </label>
    <label class="flex items-start gap-3 text-sm">
      <input type="checkbox" name="debts" value="1" checked class="mt-0.5 rounded border-line text-pine focus:ring-pine">
      <span><span class="font-medium">Deudas y pagos</span><span class="block text-muted">Saldo de cada deuda y los pagos registrados en el período.</span></span>
    </label>
    <label class="flex items-start gap-3 text-sm {{ $hasAi ? '' : 'opacity-50' }}">
      <input type="checkbox" name="ai" value="1" @disabled(! $hasAi) class="mt-0.5 rounded border-line text-pine focus:ring-pine">
      <span><span class="font-medium">Último informe de IA</span>
        <span class="block text-muted">{{ $hasAi ? 'Agrega al final el informe más reciente de la pestaña Informes del Asesor IA.' : 'Todavía no generaste ningún informe en el Asesor IA.' }}</span></span>
    </label>
  </fieldset>

  <div class="flex flex-wrap gap-3">
    <button class="btn-primary">Descargar PDF</button>
    <button class="btn-ghost" name="inline" value="1" formtarget="_blank">Ver en el navegador</button>
  </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
  const range = document.getElementById('custom-range');
  document.querySelectorAll('input[name="period"]').forEach(r => r.addEventListener('change', () => {
    range.classList.toggle('hidden', r.value !== 'custom' || !r.checked);
  }));
})();
</script>
@endpush
