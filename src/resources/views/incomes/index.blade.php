@extends('layouts.app', ['title' => 'Ingresos'])
@section('content')
<h1 class="font-display text-3xl font-bold">Ingresos mensuales</h1>
<p class="mt-1 max-w-2xl text-muted">Cargá cada ingreso por separado, en pesos o en dólares. Se usan para calcular qué parte de tu ingreso se va en gastos y deudas.</p>

@if ($incomes->isNotEmpty())
  <section class="panel mt-6 p-5" aria-label="Total de ingresos">
    <p class="text-sm text-muted">Total por mes, en pesos</p>
    <p class="num mt-1 font-display text-3xl font-bold text-pine">@money($total)</p>
    <p class="mt-1 text-sm text-muted">
      @foreach ($byCurrency as $currency => $sum)
        @money($sum, 2, $currency)@if (! $loop->last) + @endif
      @endforeach
      @if ($byCurrency->has('USD'))
        @if ($usdRate)
          <span class="block">Dólar a @money($usdRate). <a class="font-semibold text-pine hover:underline" href="{{ route('settings.edit') }}">Cambiar cotización</a></span>
        @else
          <span class="block text-amber">Sin cotización del dólar: configurala en <a class="font-semibold hover:underline" href="{{ route('settings.edit') }}">Ajustes</a> para sumar los ingresos en dólares.</span>
        @endif
      @endif
    </p>
  </section>
@endif

<form method="POST" action="{{ route('incomes.store') }}" class="panel mt-6 grid gap-3 p-4 sm:grid-cols-[1fr_12rem_8rem_auto] sm:items-end">
  @csrf
  <div>
    <label class="label" for="new_name">Nuevo ingreso</label>
    <input class="field" id="new_name" name="name" maxlength="60" required placeholder="Ej.: Sueldo, freelance, alquiler" value="{{ old('name') }}">
  </div>
  <div>
    <label class="label" for="new_amount">Monto mensual</label>
    <input class="field num" id="new_amount" name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}">
  </div>
  <div>
    <label class="label" for="new_currency">Moneda</label>
    <select class="field" id="new_currency" name="currency">
      @foreach (config('finance.currencies') as $code => $label)
        <option value="{{ $code }}" @selected(old('currency', 'ARS') === $code)>{{ $code }}</option>
      @endforeach
    </select>
  </div>
  <button class="btn-primary">Agregar</button>
</form>

<div class="panel mt-6 divide-y divide-line">
  @forelse ($incomes as $income)
    <div class="grid gap-3 p-4 sm:grid-cols-[1fr_12rem_8rem_auto_auto] sm:items-center">
      <form method="POST" action="{{ route('incomes.update', $income) }}" class="contents">
        @csrf @method('PUT')
        <div>
          <label class="sr-only" for="name-{{ $income->id }}">Nombre</label>
          <input class="field font-medium" id="name-{{ $income->id }}" name="name" value="{{ $income->name }}" maxlength="60" required>
          @if ($income->isUsd() && $usdRate)
            <p class="mt-1 text-xs text-muted">≈ @money($income->amount * $usdRate, 0) por mes</p>
          @endif
        </div>
        <div>
          <label class="sr-only" for="amount-{{ $income->id }}">Monto mensual</label>
          <input class="field num" id="amount-{{ $income->id }}" name="amount" type="number" step="0.01" min="0.01" value="{{ $income->amount }}" required>
        </div>
        <div>
          <label class="sr-only" for="currency-{{ $income->id }}">Moneda</label>
          <select class="field" id="currency-{{ $income->id }}" name="currency">
            @foreach (config('finance.currencies') as $code => $label)
              <option value="{{ $code }}" @selected($income->currency === $code)>{{ $code }}</option>
            @endforeach
          </select>
        </div>
        <button class="btn-ghost">Guardar</button>
      </form>
      <form method="POST" action="{{ route('incomes.destroy', $income) }}" onsubmit="return confirm('¿Eliminar este ingreso?')">
        @csrf @method('DELETE')
        <button class="btn-danger w-full">Eliminar</button>
      </form>
    </div>
  @empty
    <p class="p-6 text-center text-muted">Todavía no cargaste ingresos. Agregá el primero con el formulario de arriba.</p>
  @endforelse
</div>
@endsection
