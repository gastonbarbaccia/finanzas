@extends('layouts.app', ['title' => 'Gastos'])
@section('content')
@php
  $prev = $month->copy()->subMonthNoOverflow()->format('Y-m');
  $next = $month->copy()->addMonthNoOverflow()->format('Y-m');
  $isCurrent = $month->isSameMonth(now());
@endphp
<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="font-display text-3xl font-bold">Gastos</h1>
    <div class="mt-2 flex items-center gap-2">
      <a class="btn-ghost px-2.5 py-1" href="{{ route('expenses.index', array_merge($filters, ['month' => $prev])) }}" aria-label="Mes anterior">‹</a>
      <span class="min-w-[9rem] text-center font-medium">{{ ucfirst($month->translatedFormat('F Y')) }}</span>
      @unless ($isCurrent)
        <a class="btn-ghost px-2.5 py-1" href="{{ route('expenses.index', array_merge($filters, ['month' => $next])) }}" aria-label="Mes siguiente">›</a>
      @endunless
    </div>
  </div>
  <a href="{{ route('expenses.create') }}" class="btn-primary self-start sm:self-auto">Cargar gasto</a>
</header>

<form method="GET" class="panel mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
  <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
  <div>
    <label class="label" for="q">Buscar</label>
    <input class="field" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Descripción" maxlength="100">
  </div>
  <div>
    <label class="label" for="category">Categoría</label>
    <select class="field" id="category" name="category">
      <option value="">Todas</option>
      @foreach ($categories as $c)
        <option value="{{ $c->id }}" @selected(($filters['category'] ?? null) == $c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label class="label" for="status">Estado</label>
    <select class="field" id="status" name="status">
      <option value="">Todos</option>
      <option value="paid" @selected(($filters['status'] ?? null) === 'paid')>Pagados</option>
      <option value="pending" @selected(($filters['status'] ?? null) === 'pending')>Pendientes</option>
      <option value="overdue" @selected(($filters['status'] ?? null) === 'overdue')>Vencidos</option>
    </select>
  </div>
  <div class="flex items-end gap-2">
    <button class="btn-ghost">Filtrar</button>
    @if (!empty($filters['q']) || !empty($filters['category']) || !empty($filters['status']))
      <a href="{{ route('expenses.index', ['month' => $month->format('Y-m')]) }}" class="btn text-muted hover:text-ink">Limpiar</a>
    @endif
  </div>
</form>

<div class="panel overflow-hidden">
  <div class="flex items-center justify-between border-b border-line px-5 py-3">
    <p class="text-sm text-muted">{{ $expenses->total() }} {{ $expenses->total() === 1 ? 'gasto' : 'gastos' }}</p>
    <p class="text-right text-sm">
      Total: <strong class="num">@money($total)</strong>
      @if ($usdTotal > 0)<span class="text-xs text-muted">(incluye @money($usdTotal, 2, 'USD') convertidos)</span>@endif
      @if ($pendingTotal > 0)
        <span class="block text-amber sm:ml-3 sm:inline">Pendiente de pago: <strong class="num">@money($pendingTotal)</strong></span>
      @endif
    </p>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-paper/60 text-left text-muted">
        <tr>
          <th class="px-5 py-2 font-medium">Fecha</th>
          <th class="px-5 py-2 font-medium">Descripción</th>
          <th class="px-5 py-2 font-medium">Categoría</th>
          <th class="px-5 py-2 font-medium">Medio de pago</th>
          <th class="px-5 py-2 font-medium">Estado</th>
          <th class="px-5 py-2 text-right font-medium">Monto</th>
          <th class="px-5 py-2"><span class="sr-only">Acciones</span></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-line">
        @forelse ($expenses as $e)
          <tr>
            <td class="num whitespace-nowrap px-5 py-3">{{ $e->spent_on->format('d/m') }}</td>
            <td class="px-5 py-3">{{ $e->description ?: '—' }}</td>
            <td class="whitespace-nowrap px-5 py-3">
              @if ($e->category)
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $e->category->color }}"></span>{{ $e->category->name }}</span>
              @else
                <span class="text-muted">Sin categoría</span>
              @endif
            </td>
            <td class="whitespace-nowrap px-5 py-3 text-muted">{{ $e->paymentMethodLabel() }}</td>
            <td class="whitespace-nowrap px-5 py-3">
              @if ($e->isPaid())
                <span class="rounded-full bg-pine-soft px-2 py-0.5 text-xs font-semibold text-pine-dark">Pagado</span>
              @elseif ($e->isOverdue())
                <span class="rounded-full bg-brick-soft px-2 py-0.5 text-xs font-semibold text-brick">Vencido</span>
              @else
                <span class="rounded-full bg-amber-soft px-2 py-0.5 text-xs font-semibold text-amber">Pendiente</span>
              @endif
            </td>
            <td class="num whitespace-nowrap px-5 py-3 text-right font-semibold">
              @money($e->amount, 2, $e->currency)
              @if ($e->isUsd())<span class="block text-xs font-normal text-muted">≈ @money($e->amount_base, 0)</span>@endif
            </td>
            <td class="whitespace-nowrap px-5 py-3 text-right">
              <form method="POST" action="{{ route('expenses.toggle-paid', $e) }}" class="mr-3 inline">
                @csrf @method('PATCH')
                <button class="font-semibold {{ $e->isPaid() ? 'text-muted' : 'text-pine' }} hover:underline">{{ $e->isPaid() ? 'Marcar pendiente' : 'Marcar pagado' }}</button>
              </form>
              <a href="{{ route('expenses.edit', $e) }}" class="font-semibold text-pine hover:underline">Editar</a>
              <form method="POST" action="{{ route('expenses.destroy', $e) }}" class="ml-3 inline" onsubmit="return confirm('¿Eliminar este gasto?')">
                @csrf @method('DELETE')
                <button class="font-semibold text-brick hover:underline">Eliminar</button>
              </form>
            </td>
          </tr>
        @empty
           <tr><td colspan="7" class="px-5 py-10 text-center text-muted">
            No hay gastos en este período. <a href="{{ route('expenses.create') }}" class="font-semibold text-pine hover:underline">Cargá el primero</a>.
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-4">{{ $expenses->links() }}</div>
@endsection
