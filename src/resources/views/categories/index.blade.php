@extends('layouts.app', ['title' => 'Categorías'])
@section('content')
<h1 class="font-display text-3xl font-bold">Categorías y presupuestos</h1>
<p class="mt-1 max-w-2xl text-muted">El presupuesto mensual es opcional. Si lo cargás, el resumen te avisa cuando te acercás al límite.</p>

<form method="POST" action="{{ route('categories.store') }}" class="panel mt-6 grid gap-3 p-4 sm:grid-cols-[1fr_auto_12rem_auto] sm:items-end">
  @csrf
  <div>
    <label class="label" for="new_name">Nueva categoría</label>
    <input class="field" id="new_name" name="name" maxlength="50" required placeholder="Ej.: Mascotas">
  </div>
  <div>
    <label class="label" for="new_color">Color</label>
    <input class="h-10 w-14 cursor-pointer rounded-lg border border-line bg-white p-1" id="new_color" type="color" name="color" value="#12614F">
  </div>
  <div>
    <label class="label" for="new_budget">Presupuesto mensual</label>
    <input class="field num" id="new_budget" name="monthly_budget" type="number" step="0.01" min="0" placeholder="Opcional">
  </div>
  <button class="btn-primary">Agregar</button>
</form>

<div class="panel mt-6 divide-y divide-line">
  @foreach ($categories as $c)
    @php $used = $spent[$c->name]['total'] ?? 0; @endphp
    <div class="grid gap-3 p-4 sm:grid-cols-[1fr_auto_12rem_auto_auto] sm:items-center">
      <form id="cat-{{ $c->id }}" method="POST" action="{{ route('categories.update', $c) }}" class="contents">
        @csrf @method('PUT')
        <div>
          <label class="sr-only" for="name-{{ $c->id }}">Nombre</label>
          <input class="field font-medium" id="name-{{ $c->id }}" name="name" value="{{ $c->name }}" maxlength="50" required>
          <p class="mt-1 text-xs text-muted">Este mes: <span class="num">@money($used)</span>. {{ $c->expenses_count }} gastos en total</p>
        </div>
        <div>
          <label class="sr-only" for="color-{{ $c->id }}">Color</label>
          <input class="h-10 w-14 cursor-pointer rounded-lg border border-line bg-white p-1" id="color-{{ $c->id }}" type="color" name="color" value="{{ $c->color }}">
        </div>
        <div>
          <label class="sr-only" for="budget-{{ $c->id }}">Presupuesto mensual</label>
          <input class="field num" id="budget-{{ $c->id }}" name="monthly_budget" type="number" step="0.01" min="0" value="{{ $c->monthly_budget }}" placeholder="Sin presupuesto">
        </div>
        <button class="btn-ghost">Guardar</button>
      </form>
      <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('¿Eliminar la categoría? Sus gastos quedarán sin categoría.')">
        @csrf @method('DELETE')
        <button class="btn-danger w-full">Eliminar</button>
      </form>
    </div>
  @endforeach
</div>
@endsection
