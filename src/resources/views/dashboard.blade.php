@extends('layouts.app', ['title' => 'Resumen'])
@section('content')
@php
  $s = $summary;
  $prevMonth = ucfirst(now()->subMonthNoOverflow()->translatedFormat('F'));
  $hasData = $s['month_total'] > 0 || array_sum($trend['values']) > 0 || $debts->isNotEmpty();
@endphp

<header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
  <div class="max-w-2xl">
    <p class="text-sm text-muted">{{ ucfirst(now()->translatedFormat('F \d\e Y')) }}
      @if ($usdRate)
        <a href="{{ route('settings.edit') }}" class="ml-2 hover:text-ink">Dólar {{ \App\Services\ExchangeRate::TYPES[auth()->user()->usd_rate_type] ?? '' }}: <span class="num">@money($usdRate)</span></a>
      @endif
    </p>
    <h1 class="mt-1 font-display text-3xl font-bold leading-tight sm:text-4xl">
      Este mes gastaste <span class="num whitespace-nowrap">@money($s['month_total'])</span>
      @if (! is_null($s['change_pct']))
        <span class="block text-xl font-medium text-muted sm:text-2xl">
          {{ abs($s['change_pct']) }}% {{ $s['change_pct'] >= 0 ? 'más' : 'menos' }} que en {{ $prevMonth }}
        </span>
      @endif
    </h1>
  </div>
  <a href="{{ route('expenses.create') }}" class="btn-primary self-start sm:self-auto">Cargar gasto</a>
</header>

<section class="mb-8 grid grid-cols-2 overflow-hidden rounded-xl border border-line bg-line gap-px lg:grid-cols-4" aria-label="Indicadores">
  <div class="bg-white p-4">
    <p class="text-sm text-muted">Proyección a fin de mes</p>
    <p class="num mt-1 text-xl font-semibold">@money($s['projection'])</p>
    @if ($s['expense_ratio'])
      <p class="mt-1 text-xs {{ $s['expense_ratio'] > 90 ? 'text-brick' : 'text-muted' }}">{{ $s['expense_ratio'] }}% de tu ingreso</p>
    @endif
  </div>
  <div class="bg-white p-4">
    <p class="text-sm text-muted">Promedio diario</p>
    <p class="num mt-1 text-xl font-semibold">@money($s['daily_average'])</p>
    <p class="mt-1 text-xs text-muted">{{ $s['expense_count'] }} gastos registrados</p>
  </div>
  <div class="bg-white p-4">
    <p class="text-sm text-muted">Deuda pendiente</p>
    <p class="num mt-1 text-xl font-semibold {{ $s['total_debt'] > 0 ? 'text-brick' : '' }}">@money($s['total_debt'])</p>
    <p class="mt-1 text-xs text-muted">{{ $s['active_debts'] }} {{ $s['active_debts'] === 1 ? 'deuda activa' : 'deudas activas' }}</p>
  </div>
  <div class="bg-white p-4">
    <p class="text-sm text-muted">Pagos mínimos al mes</p>
    <p class="num mt-1 text-xl font-semibold">@money($s['min_payments'])</p>
    @if ($s['debt_payment_ratio'] !== null)
      <p class="mt-1 text-xs {{ $s['debt_payment_ratio'] > 35 ? 'text-brick' : 'text-muted' }}">{{ $s['debt_payment_ratio'] }}% de tu ingreso</p>
    @else
      <a href="{{ route('incomes.index') }}" class="mt-1 block text-xs font-semibold text-pine hover:underline">Cargá tus ingresos para ver el ratio</a>
    @endif
  </div>
</section>

@if ($s['free_cash'] !== null)
  <p class="mb-8 rounded-lg px-4 py-3 text-sm {{ $s['free_cash'] < 0 ? 'bg-brick-soft text-brick' : 'bg-pine-soft text-pine-dark' }}">
    @if ($s['free_cash'] >= 0)
      Tus ingresos suman <strong class="num">@money($s['income'])</strong> por mes. Si seguís a este ritmo, te quedarían <strong class="num">@money($s['free_cash'])</strong> libres este mes después de gastos y pagos mínimos.
    @else
      Tus ingresos suman <strong class="num">@money($s['income'])</strong> por mes. A este ritmo vas a cerrar el mes <strong class="num">@money(abs($s['free_cash']))</strong> por encima de tu ingreso, contando los pagos mínimos.
    @endif
  </p>
@endif

@if ($pending->isNotEmpty())
  <section class="panel mb-8 overflow-hidden" aria-labelledby="pendientes-titulo">
    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-line px-5 py-3">
      <h2 id="pendientes-titulo" class="font-display text-lg font-bold">
        Pendientes de pago: <span class="num text-amber">@money($s['pending_total'])</span>
      </h2>
      <p class="text-sm text-muted">
        {{ $s['pending_count'] }} {{ $s['pending_count'] === 1 ? 'gasto' : 'gastos' }}@if ($s['overdue_count'] > 0), <span class="font-semibold text-brick">{{ $s['overdue_count'] }} {{ $s['overdue_count'] === 1 ? 'vencido' : 'vencidos' }}</span>@endif
      </p>
    </div>
    <ul class="divide-y divide-line">
      @foreach ($pending as $e)
        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
          <div class="min-w-0">
            <p class="truncate font-medium">{{ $e->description ?: ($e->category?->name ?? 'Gasto') }}</p>
            <p class="{{ $e->isOverdue() ? 'font-semibold text-brick' : 'text-muted' }}">
              @if ($e->isOverdue())
                Venció el {{ $e->spent_on->format('d/m') }}
              @elseif ($e->spent_on->isToday())
                Vence hoy
              @else
                Vence el {{ $e->spent_on->format('d/m') }}
              @endif
            </p>
          </div>
          <div class="flex items-center gap-4">
            <span class="num font-semibold">@money($e->amount, 2, $e->currency)</span>
            <form method="POST" action="{{ route('expenses.toggle-paid', $e) }}">
              @csrf @method('PATCH')
              <button class="btn-ghost px-3 py-1.5">Marcar pagado</button>
            </form>
          </div>
        </li>
      @endforeach
    </ul>
    @if ($s['pending_count'] > $pending->count())
      <p class="border-t border-line px-5 py-3 text-sm text-muted">Y {{ $s['pending_count'] - $pending->count() }} más. Usá el filtro "Pendientes" en Gastos para verlos.</p>
    @endif
  </section>
@endif

@if ($installments->isNotEmpty())
  <section class="panel mb-8 overflow-hidden" aria-labelledby="cuotas-titulo">
    <h2 id="cuotas-titulo" class="border-b border-line px-5 py-3 font-display text-lg font-bold">Cuotas de deudas sin pagar este mes</h2>
    <ul class="divide-y divide-line">
      @foreach ($installments as $i)
        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
          <div class="flex min-w-0 flex-wrap items-center gap-2">
            <span class="font-medium">{{ $i['debt']->name }}</span>
            @include('partials.installment-badge', ['debt' => $i['debt'], 'status' => $i['status']])
          </div>
          <div class="flex items-center gap-4">
            @if ($i['debt']->minimum_payment > 0)
              <span class="num font-semibold">@money(min($i['debt']->minimum_payment, $i['debt']->balance()), 2, $i['debt']->currency)</span>
            @endif
            <a href="{{ route('debts.show', $i['debt']) }}" class="btn-ghost px-3 py-1.5">Registrar pago</a>
          </div>
        </li>
      @endforeach
    </ul>
  </section>
@endif

@if (! $hasData)
  <div class="panel p-8 text-center">
    <h2 class="font-display text-xl font-bold">Todavía no hay datos para graficar</h2>
    <p class="mx-auto mt-2 max-w-md text-muted">Cargá tus primeros gastos y deudas: los gráficos y las recomendaciones de la IA se arman a partir de ellos.</p>
    <div class="mt-5 flex justify-center gap-3">
      <a href="{{ route('expenses.create') }}" class="btn-primary">Cargar gasto</a>
      <a href="{{ route('debts.create') }}" class="btn-ghost">Registrar deuda</a>
    </div>
  </div>
@else
<div class="grid gap-6 lg:grid-cols-3">
  <section class="panel p-5 lg:col-span-2">
    <h2 class="font-display text-lg font-bold">Gasto de los últimos 12 meses</h2>
    <div class="mt-4 h-64"><canvas id="trendChart" aria-label="Gasto mensual de los últimos 12 meses" role="img"></canvas></div>
  </section>

  <section class="panel p-5">
    <h2 class="font-display text-lg font-bold">Por categoría</h2>
    @if ($categories->sum('total') > 0)
      <div class="mt-4 h-64"><canvas id="categoryChart" aria-label="Distribución del gasto por categoría" role="img"></canvas></div>
    @else
      <p class="mt-4 text-sm text-muted">Sin gastos este mes.</p>
    @endif
  </section>

  <section class="panel p-5 lg:col-span-2">
    <div class="flex items-baseline justify-between">
      <h2 class="font-display text-lg font-bold">Presupuesto vs. gastado</h2>
      <a href="{{ route('categories.index') }}" class="text-sm font-semibold text-pine hover:underline">Editar presupuestos</a>
    </div>
    @forelse ($budgeted as $c)
      @php $pct = $c['budget'] > 0 ? round($c['total'] / $c['budget'] * 100) : 0; @endphp
      <div class="mt-4">
        <div class="flex justify-between text-sm">
          <span class="font-medium">{{ $c['name'] }}</span>
          <span class="num {{ $pct > 100 ? 'text-brick font-semibold' : 'text-muted' }}">@money($c['total']) de @money($c['budget'])</span>
        </div>
        <div class="mt-1 h-2 rounded-full bg-paper">
          <div class="h-2 rounded-full {{ $pct > 100 ? 'bg-brick' : ($pct > 80 ? 'bg-amber' : 'bg-pine') }}" style="width: {{ min(100, $pct) }}%"></div>
        </div>
      </div>
    @empty
      <p class="mt-4 text-sm text-muted">Asigná un presupuesto mensual a tus categorías para controlar cuánto te queda en cada una.</p>
    @endforelse
  </section>

  <section class="panel p-5">
    <h2 class="font-display text-lg font-bold">Medios de pago</h2>
    @if ($methods->isNotEmpty())
      <div class="mt-4 h-56"><canvas id="methodChart" aria-label="Gasto por medio de pago" role="img"></canvas></div>
    @else
      <p class="mt-4 text-sm text-muted">Sin gastos este mes.</p>
    @endif
  </section>

  <section class="panel p-5 lg:col-span-2">
    <div class="flex items-baseline justify-between">
      <h2 class="font-display text-lg font-bold">Deudas activas</h2>
      <a href="{{ route('debts.index') }}" class="text-sm font-semibold text-pine hover:underline">Ver todas</a>
    </div>
    @if ($debts->isNotEmpty())
      <div class="mt-4" style="height: {{ max(120, $debts->count() * 48) }}px"><canvas id="debtChart" aria-label="Saldo de cada deuda" role="img"></canvas></div>
    @else
      <p class="mt-4 text-sm text-muted">No tenés deudas activas.</p>
    @endif
  </section>

  <section class="panel p-5">
    <div class="flex items-baseline justify-between">
      <h2 class="font-display text-lg font-bold">Últimos gastos</h2>
      <a href="{{ route('expenses.index') }}" class="text-sm font-semibold text-pine hover:underline">Ver todos</a>
    </div>
    <ul class="mt-3 divide-y divide-line">
      @forelse ($recent as $e)
        <li class="flex items-center justify-between gap-3 py-2 text-sm">
          <span class="flex min-w-0 items-center gap-2">
            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $e->category?->color ?? '#A0AEC0' }}"></span>
            <span class="truncate">{{ $e->description ?: ($e->category?->name ?? 'Gasto') }}</span>
            @unless ($e->isPaid())<span class="shrink-0 rounded-full bg-amber-soft px-1.5 text-xs font-semibold text-amber">pendiente</span>@endunless
          </span>
          <span class="num shrink-0 font-medium">@money($e->amount, 2, $e->currency)</span>
        </li>
      @empty
        <li class="py-2 text-sm text-muted">Sin gastos todavía.</li>
      @endforelse
    </ul>
  </section>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
  if (typeof Chart === 'undefined') return;
  const currency = @json(config('finance.currency_symbol'));
  const fmt = v => currency + ' ' + Number(v).toLocaleString('es-AR', { maximumFractionDigits: 0 });
  Chart.defaults.font.family = '"Instrument Sans", system-ui, sans-serif';
  Chart.defaults.color = '#5B6773';
  Chart.defaults.plugins.tooltip.callbacks.label = ctx => ' ' + fmt(ctx.parsed.y ?? ctx.parsed.x ?? ctx.parsed);
  const grid = { color: '#E4E8E2' };

  const trend = @json($trend);
  const el = id => document.getElementById(id);

  if (el('trendChart')) new Chart(el('trendChart'), {
    type: 'bar',
    data: { labels: trend.labels, datasets: [{ data: trend.values, borderRadius: 4,
      backgroundColor: trend.values.map((_, i) => i === trend.values.length - 1 ? '#12614F' : '#9CC7B8') }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } },
      scales: { y: { grid, ticks: { callback: fmt } }, x: { grid: { display: false } } } }
  });

  const cats = @json($chartCategories);
  if (el('categoryChart')) new Chart(el('categoryChart'), {
    type: 'doughnut',
    data: { labels: cats.map(c => c.name), datasets: [{ data: cats.map(c => c.total), backgroundColor: cats.map(c => c.color), borderWidth: 2, borderColor: '#fff' }] },
    options: { maintainAspectRatio: false, cutout: '62%',
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10 } },
        tooltip: { callbacks: { label: ctx => ' ' + fmt(ctx.parsed) } } } }
  });

  const methods = @json($methods);
  if (el('methodChart')) new Chart(el('methodChart'), {
    type: 'pie',
    data: { labels: methods.map(m => m.method), datasets: [{ data: methods.map(m => m.total),
      backgroundColor: ['#12614F', '#9A6212', '#B4442E', '#2B6CB0', '#6B46C1', '#718096'], borderColor: '#fff', borderWidth: 2 }] },
    options: { maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10 } },
        tooltip: { callbacks: { label: ctx => ' ' + fmt(ctx.parsed) } } } }
  });

  const debts = @json($chartDebts);
  if (el('debtChart')) new Chart(el('debtChart'), {
    type: 'bar',
    data: { labels: debts.map(d => d.name), datasets: [
      { label: 'Pagado', data: debts.map(d => d.paid), backgroundColor: '#12614F', borderRadius: 3 },
      { label: 'Pendiente', data: debts.map(d => d.balance), backgroundColor: '#E9B4A7', borderRadius: 3 },
    ] },
    options: { indexAxis: 'y', maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } },
        tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.parsed.x) } } },
      scales: { x: { stacked: true, grid, ticks: { callback: fmt } }, y: { stacked: true, grid: { display: false } } } }
  });
})();
</script>
@endpush
