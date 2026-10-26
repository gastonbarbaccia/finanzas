@extends('layouts.app', ['title' => 'Deudas'])
@section('content')
<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="font-display text-3xl font-bold">Deudas</h1>
    <p class="mt-1 text-sm text-muted">{{ $active->count() }} {{ $active->count() === 1 ? 'activa' : 'activas' }}, {{ $paid->count() }} {{ $paid->count() === 1 ? 'cancelada' : 'canceladas' }}</p>
    @if ($active->isNotEmpty())
      <p class="mt-1 text-muted">Debés <strong class="num text-brick">@money($totalBalance)</strong> en total, con pagos mínimos de <strong class="num text-ink">@money($totalMinimum)</strong> por mes.
        @if ($active->contains->isUsd())<span class="text-xs">Las deudas en dólares se suman convertidas a pesos.</span>@endif</p>
    @endif
  </div>
  <a href="{{ route('debts.create') }}" class="btn-primary self-start sm:self-auto">Registrar deuda</a>
</header>

@forelse ($active as $d)
  <a href="{{ route('debts.show', $d) }}" class="panel mb-3 block p-5 transition hover:border-pine">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <div>
        <h2 class="flex flex-wrap items-center gap-2 font-display text-lg font-bold">
          {{ $d->name }}
          @include('partials.installment-badge', ['debt' => $d, 'status' => $statuses[$d->id] ?? null])
        </h2>
        <p class="text-sm text-muted">{{ $d->typeLabel() }}@if ($d->creditor), {{ $d->creditor }}@endif
          @if ($d->interest_rate > 0), {{ rtrim(rtrim(number_format($d->interest_rate, 2, ',', '.'), '0'), ',') }}% anual @endif
          @if ($d->due_day), vence el día {{ $d->due_day }} @endif</p>
      </div>
      <p class="text-right">
        <span class="num block text-lg font-semibold text-brick">@money($d->balance(), 2, $d->currency)</span>
        <span class="text-xs text-muted">pendiente de @money($d->principal, 2, $d->currency)</span>
        @if ($d->isUsd() && $usdRate)
          <span class="block text-xs text-muted">≈ @money($d->toBase($d->balance(), $usdRate), 0)</span>
        @endif
      </p>
    </div>
    <div class="mt-3 h-2 rounded-full bg-brick-soft" role="progressbar" aria-valuenow="{{ $d->progress() }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso de pago">
      <div class="h-2 rounded-full bg-pine" style="width: {{ $d->progress() }}%"></div>
    </div>
    <p class="mt-1 text-xs text-muted">{{ $d->progress() }}% pagado</p>
  </a>
@empty
  <div class="panel p-8 text-center">
    <h2 class="font-display text-xl font-bold">No tenés deudas activas</h2>
    <p class="mt-2 text-muted">Registrá tarjetas, préstamos o deudas personales para seguir cuánto falta pagar.</p>
  </div>
@endforelse

@if ($paid->isNotEmpty())
  <h2 class="mb-3 mt-10 font-display text-xl font-bold">Canceladas</h2>
  <div class="panel divide-y divide-line">
    @foreach ($paid as $d)
      @php $last = $d->lastPaymentDate(); @endphp
      <a href="{{ route('debts.show', $d) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm hover:bg-paper">
        <span class="flex items-center gap-2">
          <span class="rounded-full bg-pine-soft px-2 py-0.5 text-xs font-semibold text-pine-dark">Cancelada</span>
          {{ $d->name }}
        </span>
        <span class="num text-muted">@money($d->principal, 2, $d->currency)@if ($last), último pago el {{ $last->format('d/m/Y') }}@endif</span>
      </a>
    @endforeach
  </div>
@endif
@endsection
