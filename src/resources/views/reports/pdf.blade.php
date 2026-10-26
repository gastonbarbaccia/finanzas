@php
  use App\Support\Money;
  $s = $summary;
  $periodLabel = $from->isSameMonth($to) && $from->day === 1 && $to->isLastOfMonth()
      ? ucfirst($from->translatedFormat('F Y'))
      : $from->format('d/m/Y') . ' al ' . $to->format('d/m/Y');
  $maxCat = max(0.01, $categories->max('total') ?? 0);
  $maxMonth = max(0.01, $monthly->max('total') ?? 0);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte de finanzas, {{ $periodLabel }}</title>
<style>
  @page { margin: 26mm 16mm 20mm 16mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #17212B; line-height: 1.4; }
  header { position: fixed; top: -18mm; left: 0; right: 0; height: 12mm; border-bottom: 1.5pt solid #12614F; }
  header .brand { font-size: 12pt; font-weight: bold; color: #0B4639; }
  header .meta { position: absolute; right: 0; top: 1mm; text-align: right; font-size: 8pt; color: #5B6773; }
  footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7.5pt; color: #5B6773; }
  h1 { font-size: 18pt; margin: 0 0 2pt; color: #0B4639; }
  h2 { font-size: 12pt; margin: 18pt 0 6pt; color: #0B4639; padding-bottom: 3pt; border-bottom: 0.75pt solid #D5DBD3; }
  .muted { color: #5B6773; }
  .lead { font-size: 10.5pt; margin: 0 0 10pt; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; font-size: 8pt; font-weight: bold; color: #5B6773; padding: 4pt 5pt; border-bottom: 0.75pt solid #D5DBD3; }
  td { padding: 4pt 5pt; border-bottom: 0.5pt solid #E4E8E2; vertical-align: middle; }
  .num { text-align: right; white-space: nowrap; }
  .kpis td { border: 0.75pt solid #D5DBD3; padding: 7pt 8pt; width: 25%; vertical-align: top; }
  .kpi-label { font-size: 7.5pt; color: #5B6773; }
  .kpi-value { font-size: 12.5pt; font-weight: bold; margin-top: 2pt; }
  .kpi-note { font-size: 7.5pt; color: #5B6773; margin-top: 2pt; }
  .bar-bg { background: #EEF1EC; height: 7pt; width: 100%; }
  .bar { height: 7pt; background: #12614F; }
  .dot { width: 8pt; height: 8pt; }
  .tag { font-size: 7pt; font-weight: bold; padding: 1pt 4pt; white-space: nowrap; }
  .keep { page-break-inside: avoid; }
  .tag-paid { background: #DCEDE5; color: #0B4639; }
  .tag-pending { background: #F5E9CF; color: #9A6212; }
  .tag-overdue { background: #F6E1DB; color: #B4442E; }
  .red { color: #B4442E; }
  .amber { color: #9A6212; }
  .green { color: #12614F; }
  .box { border: 0.75pt solid #D5DBD3; padding: 8pt 10pt; margin-top: 8pt; }
  .page-break { page-break-before: always; }
  .ai h2 { font-size: 11pt; border: none; margin: 10pt 0 3pt; }
  .ai h3 { font-size: 10pt; margin: 8pt 0 2pt; }
  .ai p { margin: 0 0 5pt; }
  .ai ul, .ai ol { margin: 0 0 5pt 14pt; padding: 0; }
  .ai table td, .ai table th { font-size: 8.5pt; }
  .small { font-size: 8pt; }
</style>
</head>
<body>
<header>
  <div class="brand">{{ config('app.name') }}</div>
  <div class="meta">{{ $user->name }}<br>Generado el {{ $generatedAt->format('d/m/Y H:i') }}</div>
</header>
<footer>Reporte de uso personal. Los montos se expresan en la moneda configurada en la app.</footer>

<h1>Reporte de finanzas</h1>
<p class="lead muted">Período: <strong style="color:#17212B">{{ $periodLabel }}</strong>@if ($usdRate)<span class="small">. Montos en pesos; dólar {{ $usdRateType === 'Manual (la cargo yo)' ? 'cargado a mano' : $usdRateType }} a {{ Money::format($usdRate) }}.</span>@endif</p>

{{-- Resumen --}}
<table class="kpis">
  <tr>
    <td>
      <div class="kpi-label">Total gastado</div>
      <div class="kpi-value">{{ Money::format($s['total']) }}</div>
      <div class="kpi-note">
        @if (! is_null($s['change_pct']))
          {{ number_format(abs($s['change_pct']), 1, ',', '.') }}% {{ $s['change_pct'] >= 0 ? 'más' : 'menos' }} que el período anterior
        @else
          {{ $s['count'] }} gastos
        @endif
      </div>
    </td>
    <td>
      <div class="kpi-label">Pagado</div>
      <div class="kpi-value green">{{ Money::format($s['paid']) }}</div>
      <div class="kpi-note">@if ($s['usd_spent'] > 0)Incluye {{ Money::format($s['usd_spent'], 2, 'USD') }} en dólares @else {{ $s['count'] }} gastos registrados @endif</div>
    </td>
    <td>
      <div class="kpi-label">Pendiente de pago</div>
      <div class="kpi-value {{ $s['pending'] > 0 ? 'amber' : '' }}">{{ Money::format($s['pending']) }}</div>
      <div class="kpi-note">{{ $s['pending_count'] }} {{ $s['pending_count'] === 1 ? 'gasto' : 'gastos' }}</div>
    </td>
    <td>
      <div class="kpi-label">Promedio diario</div>
      <div class="kpi-value">{{ Money::format($s['daily_average']) }}</div>
      <div class="kpi-note">
        @if ($s['income_pct'] !== null)
          Gastaste el {{ number_format($s['income_pct'], 1, ',', '.') }}% de tu ingreso
        @else
          Sin ingreso cargado
        @endif
      </div>
    </td>
  </tr>
</table>

@if ($overdue->isNotEmpty())
  <div class="box" style="border-color:#B4442E;">
    <strong class="red">Gastos vencidos sin pagar ({{ $overdue->count() }}, {{ Money::format($overdue->sum('amount_base')) }}):</strong>
    <span class="small">
      @foreach ($overdue->take(6) as $e){{ $e->description ?: ($e->category?->name ?? 'Gasto') }} ({{ $e->spent_on->format('d/m') }}, {{ Money::format($e->amount, 2, $e->currency) }})@if (! $loop->last), @endif @endforeach
      @if ($overdue->count() > 6) y {{ $overdue->count() - 6 }} más. @endif
    </span>
  </div>
@endif

{{-- Categorías --}}
<div class="keep">
<h2>Gasto por categoría</h2>
@if ($categories->isEmpty())
  <p class="muted">No hay gastos en este período.</p>
@else
<table>
  <thead>
    <tr>
      <th style="width:12pt"></th><th>Categoría</th><th class="num">Gastos</th><th class="num">Total</th>
      <th class="num">% del total</th><th style="width:28%"></th><th class="num">Presupuesto</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($categories as $c)
      <tr>
        <td><div class="dot" style="background: {{ $c['color'] }}"></div></td>
        <td>{{ $c['name'] }}</td>
        <td class="num">{{ $c['count'] }}</td>
        <td class="num"><strong>{{ Money::format($c['total']) }}</strong></td>
        <td class="num">{{ number_format($c['share'], 1, ',', '.') }}%</td>
        <td><div class="bar-bg"><div class="bar" style="width: {{ max(1, round($c['total'] / $maxCat * 100)) }}%; background: {{ $c['color'] }}"></div></div></td>
        <td class="num {{ ($c['budget_pct'] ?? 0) > 100 ? 'red' : '' }}">
          @if ($c['budget']) {{ $c['budget_pct'] }}% de {{ Money::format($c['budget'], 0) }} @else <span class="muted">—</span> @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@if ($months > 1)<p class="small muted">El presupuesto se calcula como el presupuesto mensual por {{ $months }} meses.</p>@endif
@endif
</div>

{{-- Evolución mensual --}}
@if ($monthly->isNotEmpty())
<div class="keep">
<h2>Evolución mensual</h2>
<table>
  <tbody>
    @foreach ($monthly as $m)
      <tr>
        <td style="width:22%">{{ $m['label'] }}</td>
        <td><div class="bar-bg"><div class="bar" style="width: {{ max(1, round($m['total'] / $maxMonth * 100)) }}%"></div></div></td>
        <td class="num" style="width:22%"><strong>{{ Money::format($m['total']) }}</strong></td>
      </tr>
    @endforeach
  </tbody>
</table>
</div>
@endif

{{-- Medios de pago --}}
@if ($methods->isNotEmpty())
<div class="keep">
<h2>Medios de pago</h2>
<table>
  <tbody>
    @foreach ($methods as $m)
      <tr>
        <td>{{ $m['label'] }}</td>
        <td class="num" style="width:20%">{{ $s['total'] > 0 ? number_format($m['total'] / $s['total'] * 100, 1, ',', '.') : 0 }}%</td>
        <td class="num" style="width:25%"><strong>{{ Money::format($m['total']) }}</strong></td>
      </tr>
    @endforeach
  </tbody>
</table>
</div>
@endif

{{-- Deudas --}}
@if (($options['debts'] ?? false) && $debts->isNotEmpty())
<div class="keep">
<h2>Deudas</h2>
<table>
  <thead>
    <tr><th>Deuda</th><th>Estado</th><th class="num">Tasa anual</th><th class="num">Pago mínimo</th><th class="num">Pagado en el período</th><th class="num">Saldo actual</th></tr>
  </thead>
  <tbody>
    @foreach ($debts as $row)
      @php $d = $row['debt']; @endphp
      <tr>
        <td><strong>{{ $d->name }}</strong><br><span class="small muted">{{ $d->typeLabel() }}@if ($d->creditor), {{ $d->creditor }}@endif</span></td>
        <td>
          @if ($d->isPaid())
            <span class="tag tag-paid">Cancelada</span>
          @elseif ($row['status'] === 'overdue')
            <span class="tag tag-overdue">Cuota vencida</span>
          @elseif ($row['status'] === 'pending')
            <span class="tag tag-pending">Cuota pendiente</span>
          @elseif ($row['status'] === 'paid')
            <span class="tag tag-paid">Cuota al día</span>
          @elseif ($row['status'] === 'not_started')
            <span class="tag" style="background:#EEF1EC;color:#5B6773">Aún no empieza</span>
          @else
            <span class="small muted">Activa</span>
          @endif
        </td>
        <td class="num">{{ number_format($d->interest_rate, 2, ',', '.') }}%</td>
        <td class="num">{{ Money::format($d->minimum_payment, 2, $d->currency) }}</td>
        <td class="num">{{ Money::format($row['paid_in_period'], 2, $d->currency) }}</td>
        <td class="num {{ $row['balance'] > 0 ? 'red' : 'green' }}"><strong>{{ Money::format($row['balance'], 2, $d->currency) }}</strong>
          @if ($d->isUsd())<br><span class="small muted">≈ {{ Money::format($row['balance_base'], 0) }}</span>@endif
          <br><span class="small muted">{{ $d->progress() }}% pagado</span></td>
      </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4"><strong>Total en pesos</strong>@if ($debts->contains(fn ($r) => $r['debt']->isUsd()))<br><span class="small muted">Deudas en dólares convertidas a {{ Money::format($usdRate ?? 0) }} por dólar</span>@endif</td>
      <td class="num"><strong>{{ Money::format($debts->sum('paid_base')) }}</strong></td>
      <td class="num red"><strong>{{ Money::format($debts->sum('balance_base')) }}</strong></td>
    </tr>
  </tfoot>
</table>
</div>

@php $payments = $debts->flatMap(fn ($r) => $r['payments']->map(fn ($p) => ['debt' => $r['debt']->name, 'currency' => $r['debt']->currency, 'p' => $p]))->sortBy(fn ($x) => $x['p']->paid_on); @endphp
@if ($payments->isNotEmpty())
  <p class="small" style="margin-top:8pt"><strong>Pagos registrados en el período</strong></p>
  <table class="small">
    @foreach ($payments as $x)
      <tr><td style="width:15%">{{ $x['p']->paid_on->format('d/m/Y') }}</td><td>{{ $x['debt'] }}@if ($x['p']->note) <span class="muted">({{ $x['p']->note }})</span>@endif</td><td class="num">{{ Money::format($x['p']->amount, 2, $x['currency']) }}</td></tr>
    @endforeach
  </table>
@endif
@endif

{{-- Detalle --}}
@if (($options['details'] ?? false) && $expenses->isNotEmpty())
<h2 class="page-break" style="margin-top:0">Detalle de gastos</h2>
<table class="small">
  <thead>
    <tr><th>Fecha</th><th>Descripción</th><th>Categoría</th><th>Medio de pago</th><th>Estado</th><th class="num">Monto</th></tr>
  </thead>
  <tbody>
    @foreach ($expenses as $e)
      <tr>
        <td>{{ $e->spent_on->format('d/m/Y') }}</td>
        <td>{{ $e->description ?: '—' }}</td>
        <td>{{ $e->category?->name ?? 'Sin categoría' }}</td>
        <td>{{ $e->paymentMethodLabel() }}</td>
        <td>
          @if ($e->isPaid()) <span class="tag tag-paid">Pagado</span>
          @elseif ($e->isOverdue()) <span class="tag tag-overdue">Vencido</span>
          @else <span class="tag tag-pending">Pendiente</span> @endif
        </td>
        <td class="num">{{ Money::format($e->amount, 2, $e->currency) }}@if ($e->isUsd())<br><span class="muted">≈ {{ Money::format($e->amount_base, 0) }}</span>@endif</td>
      </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr><td colspan="5"><strong>Total ({{ $expenses->count() }} gastos)</strong></td><td class="num"><strong>{{ Money::format($s['total']) }}</strong></td></tr>
  </tfoot>
</table>
@endif

{{-- Informe de IA --}}
@if ($aiReport)
<h2 class="page-break" style="margin-top:0">Último informe del Asesor IA</h2>
<p class="small muted">Generado el {{ $aiReport->created_at->format('d/m/Y H:i') }} con {{ config("ai.providers.{$aiReport->provider}.label", $aiReport->provider) }}.</p>
<div class="ai">
  {!! \Illuminate\Support\Str::markdown($aiReport->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
</div>
@endif
</body>
</html>
