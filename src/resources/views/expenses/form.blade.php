@extends('layouts.app', ['title' => $expense->exists ? 'Editar gasto' : 'Cargar gasto'])
@section('content')
<h1 class="mb-6 font-display text-3xl font-bold">{{ $expense->exists ? 'Editar gasto' : 'Cargar gasto' }}</h1>

<form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="panel max-w-2xl space-y-5 p-6">
  @csrf
  @if ($expense->exists) @method('PUT') @endif

  <div class="grid gap-5 sm:grid-cols-2">
    <div>
      <label class="label" for="amount">Monto</label>
      <input class="field num text-lg" id="amount" name="amount" type="number" step="0.01" min="0.01" inputmode="decimal"
             value="{{ old('amount', $expense->amount) }}" required autofocus>
    </div>
    <div>
      <label class="label" for="spent_on">Fecha o vencimiento</label>
      <input class="field" id="spent_on" name="spent_on" type="date" value="{{ old('spent_on', $expense->spent_on?->format('Y-m-d')) }}" required>
    </div>
  </div>

  @include('partials.currency-fields', ['model' => $expense, 'usdRate' => $usdRate])

  <div>
    <label class="label" for="description">Descripción</label>
    <input class="field" id="description" name="description" maxlength="255" value="{{ old('description', $expense->description) }}" placeholder="Ej.: supermercado semanal">
  </div>

  <div class="grid gap-5 sm:grid-cols-2">
    <div>
      <label class="label" for="category_id">Categoría</label>
      <select class="field" id="category_id" name="category_id">
        <option value="">Sin categoría</option>
        @foreach ($categories as $c)
          <option value="{{ $c->id }}" @selected(old('category_id', $expense->category_id) == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="label" for="payment_method">Medio de pago</label>
      <select class="field" id="payment_method" name="payment_method">
        <option value="">Sin especificar</option>
        @foreach (\App\Models\Expense::PAYMENT_METHODS as $key => $label)
          <option value="{{ $key }}" @selected(old('payment_method', $expense->payment_method) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="rounded-lg border border-line bg-paper/60 p-4">
    <input type="hidden" name="is_paid" value="0">
    <label class="flex items-center gap-3 font-medium">
      <input type="checkbox" name="is_paid" value="1" class="h-4 w-4 rounded border-line text-pine focus:ring-pine"
             @checked(old('is_paid', $expense->exists ? $expense->isPaid() : true))>
      Ya está pagado
    </label>
    <p class="mt-1 pl-7 text-xs text-muted">Desmarcalo para anotar una factura, cuota o servicio que todavía tenés que pagar. En ese caso la fecha es el vencimiento.</p>
  </div>

  <div class="flex gap-3 pt-2">
    <button class="btn-primary">{{ $expense->exists ? 'Guardar cambios' : 'Guardar gasto' }}</button>
    <a href="{{ route('expenses.index') }}" class="btn-ghost">Cancelar</a>
  </div>
</form>
@endsection
