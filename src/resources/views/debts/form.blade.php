@extends('layouts.app', ['title' => $debt->exists ? 'Editar deuda' : 'Registrar deuda'])
@section('content')
<h1 class="mb-6 font-display text-3xl font-bold">{{ $debt->exists ? 'Editar deuda' : 'Registrar deuda' }}</h1>

<form method="POST" action="{{ $debt->exists ? route('debts.update', $debt) : route('debts.store') }}" class="panel max-w-2xl space-y-5 p-6">
  @csrf
  @if ($debt->exists) @method('PUT') @endif

  <div class="grid gap-5 sm:grid-cols-2">
    <div>
      <label class="label" for="name">Nombre</label>
      <input class="field" id="name" name="name" maxlength="100" value="{{ old('name', $debt->name) }}" required placeholder="Ej.: Visa Banco X">
    </div>
    <div>
      <label class="label" for="type">Tipo</label>
      <select class="field" id="type" name="type" required>
        @foreach (\App\Models\Debt::TYPES as $key => $label)
          <option value="{{ $key }}" @selected(old('type', $debt->type) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="grid gap-5 sm:grid-cols-2">
    <div>
      <label class="label" for="creditor">Acreedor</label>
      <input class="field" id="creditor" name="creditor" maxlength="100" value="{{ old('creditor', $debt->creditor) }}" placeholder="Banco, persona o comercio">
    </div>
    <div>
      <label class="label" for="principal">Monto total adeudado</label>
      <input class="field num" id="principal" name="principal" type="number" step="0.01" min="0.01" value="{{ old('principal', $debt->principal) }}" required>
    </div>
  </div>

  @include('partials.currency-fields', ['model' => $debt, 'usdRate' => $usdRate])
  <p class="-mt-3 text-xs text-muted">Los montos de la deuda, el pago mínimo y los pagos se cargan en la moneda elegida.</p>

  <div class="grid gap-5 sm:grid-cols-3">
    <div>
      <label class="label" for="interest_rate">Tasa anual (%)</label>
      <input class="field num" id="interest_rate" name="interest_rate" type="number" step="0.01" min="0" value="{{ old('interest_rate', $debt->interest_rate) }}" placeholder="0">
    </div>
    <div>
      <label class="label" for="minimum_payment">Pago mínimo mensual</label>
      <input class="field num" id="minimum_payment" name="minimum_payment" type="number" step="0.01" min="0" value="{{ old('minimum_payment', $debt->minimum_payment) }}" placeholder="0">
    </div>
    <div>
      <label class="label" for="due_day">Día de vencimiento</label>
      <input class="field num" id="due_day" name="due_day" type="number" min="1" max="31" value="{{ old('due_day', $debt->due_day) }}" placeholder="1 a 31">
    </div>
  </div>

  <div>
    <label class="label" for="started_on">Fecha de inicio</label>
    <input class="field sm:w-1/2" id="started_on" name="started_on" type="date" value="{{ old('started_on', $debt->started_on?->format('Y-m-d')) }}">
  </div>

  <div>
    <label class="label" for="notes">Notas</label>
    <textarea class="field" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes', $debt->notes) }}</textarea>
  </div>

  <div class="flex gap-3 pt-2">
    <button class="btn-primary">{{ $debt->exists ? 'Guardar cambios' : 'Registrar deuda' }}</button>
    <a href="{{ $debt->exists ? route('debts.show', $debt) : route('debts.index') }}" class="btn-ghost">Cancelar</a>
  </div>
</form>
@endsection
