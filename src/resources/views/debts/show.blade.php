@extends('layouts.app', ['title' => $debt->name])
@section('content')
<a href="{{ route('debts.index') }}" class="text-sm font-semibold text-pine hover:underline">‹ Deudas</a>
<header class="mb-6 mt-2 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
  <div>
    <h1 class="flex flex-wrap items-center gap-3 font-display text-3xl font-bold">
      {{ $debt->name }}
      @if ($debt->isPaid())
        <span class="rounded-full bg-pine-soft px-2.5 py-0.5 font-sans text-sm font-semibold text-pine-dark">Cancelada</span>
      @else
        <span class="rounded-full bg-brick-soft px-2.5 py-0.5 font-sans text-sm font-semibold text-brick">Activa</span>
      @endif
    </h1>
    <p class="text-muted">{{ $debt->typeLabel() }}@if ($debt->creditor), {{ $debt->creditor }}@endif</p>
  </div>
  <div class="flex gap-2">
    <a href="{{ route('debts.edit', $debt) }}" class="btn-ghost">Editar</a>
    <form method="POST" action="{{ route('debts.destroy', $debt) }}" onsubmit="return confirm('¿Eliminar esta deuda y todos sus pagos?')">
      @csrf @method('DELETE')
      <button class="btn-danger">Eliminar</button>
    </form>
  </div>
</header>

<section class="panel mb-6 p-5">
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div><p class="text-sm text-muted">Saldo pendiente</p><p class="num text-xl font-semibold {{ $debt->isPaid() ? 'text-pine' : 'text-brick' }}">@money($debt->balance(), 2, $debt->currency)</p>@if ($debt->isUsd() && $usdRate)<p class="num text-xs text-muted">≈ @money($debt->toBase($debt->balance(), $usdRate), 0)</p>@endif</div>
    <div><p class="text-sm text-muted">Pagado</p><p class="num text-xl font-semibold">@money($debt->paidAmount(), 2, $debt->currency)</p></div>
    <div><p class="text-sm text-muted">Tasa anual</p><p class="num text-xl font-semibold">{{ number_format($debt->interest_rate, 2, ',', '.') }}%</p></div>
    <div><p class="text-sm text-muted">Pago mínimo</p><p class="num text-xl font-semibold">@money($debt->minimum_payment, 2, $debt->currency)</p></div>
  </div>
  <div class="mt-4 h-2.5 rounded-full bg-brick-soft"><div class="h-2.5 rounded-full bg-pine" style="width: {{ $debt->progress() }}%"></div></div>
  <p class="mt-1 text-xs text-muted">{{ $debt->progress() }}% pagado de @money($debt->principal, 2, $debt->currency)
    @if ($debt->due_day), vence el día {{ $debt->due_day }} de cada mes @endif</p>
  @php $status = $debt->installmentStatus(); @endphp
  @if ($status)
    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
      @include('partials.installment-badge', ['debt' => $debt, 'status' => $status])
      @unless ($status === 'not_started')
        <span class="text-muted">Pagado este mes: <span class="num">@money($debt->paidThisMonth(), 2, $debt->currency)</span></span>
      @endunless
    </div>
  @endif
  @if ($debt->notes)<p class="mt-4 whitespace-pre-line text-sm">{{ $debt->notes }}</p>@endif
</section>

<div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
  @unless ($debt->isPaid())
  <div class="space-y-3">
  <form method="POST" action="{{ route('debts.payments.store', $debt) }}" class="panel h-fit space-y-4 p-5">
    @csrf
    <h2 class="font-display text-lg font-bold">Registrar pago</h2>
    <div>
      <label class="label" for="amount">Monto ({{ \App\Support\Money::symbol($debt->currency) }})</label>
      <input class="field num" id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $debt->balance() }}"
             value="{{ old('amount', $debt->minimum_payment > 0 ? min($debt->minimum_payment, $debt->balance()) : '') }}" required>
    </div>
    <div>
      <label class="label" for="paid_on">Fecha</label>
      <input class="field" id="paid_on" name="paid_on" type="date" value="{{ old('paid_on', now()->format('Y-m-d')) }}" required>
    </div>
    <div>
      <label class="label" for="note">Nota</label>
      <input class="field" id="note" name="note" maxlength="255" value="{{ old('note') }}" placeholder="Opcional">
    </div>
    <button class="btn-primary w-full">Registrar pago</button>
  </form>
  <form method="POST" action="{{ route('debts.settle', $debt) }}" class="panel p-5"
        onsubmit="return confirm('Se va a registrar un pago de {{ \App\Support\Money::format($debt->balance(), 2, $debt->currency) }} con fecha de hoy y la deuda quedará cancelada. ¿Continuar?')">
    @csrf
    <p class="text-sm text-muted">¿Ya la pagaste completa?</p>
    <button class="btn-ghost mt-2 w-full">Saldar deuda (@money($debt->balance(), 2, $debt->currency))</button>
  </form>
  </div>
  @endunless

  <section class="panel overflow-hidden {{ $debt->isPaid() ? 'lg:col-span-2' : '' }}">
    <h2 class="border-b border-line px-5 py-3 font-display text-lg font-bold">Historial de pagos</h2>
    <ul class="divide-y divide-line">
      @forelse ($payments as $p)
        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
          <div>
            <p class="num font-medium">{{ $p->paid_on->format('d/m/Y') }}</p>
            @if ($p->note)<p class="text-muted">{{ $p->note }}</p>@endif
          </div>
          <div class="flex items-center gap-4">
            <span class="num font-semibold">@money($p->amount, 2, $debt->currency)</span>
            <form method="POST" action="{{ route('debts.payments.destroy', [$debt, $p]) }}" onsubmit="return confirm('¿Eliminar este pago?')">
              @csrf @method('DELETE')
              <button class="text-brick hover:underline" aria-label="Eliminar pago">Eliminar</button>
            </form>
          </div>
        </li>
      @empty
        <li class="px-5 py-8 text-center text-sm text-muted">Todavía no registraste pagos.</li>
      @endforelse
    </ul>
  </section>
</div>
@endsection
