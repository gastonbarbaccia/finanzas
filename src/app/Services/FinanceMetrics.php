<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula métricas agregadas de gastos y deudas de un usuario.
 * Todas las consultas se filtran por el usuario recibido.
 */
class FinanceMetrics
{
    /** Cotización del dólar vigente para este usuario (pesos por dólar). */
    public readonly ?float $usdRate;

    public function __construct(private User $user)
    {
        $this->usdRate = ExchangeRate::for($user);
    }

    /** Ingreso mensual expresado en pesos. */
    public function incomeBase(): float
    {
        return (float) $this->user->incomes()->get()
            ->sum(fn ($i) => ExchangeRate::toBase((float) $i->amount, $i->currency, $this->usdRate));
    }

    public function monthTotal(Carbon $month): float
    {
        return (float) $this->user->expenses()
            ->whereBetween('spent_on', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount_base');
    }

    public function summary(): array
    {
        $now = now();
        $current = $this->monthTotal($now);
        $previous = $this->monthTotal($now->copy()->subMonthNoOverflow());

        $dailyAverage = $current / max(1, $now->day);
        $projection = $dailyAverage * $now->daysInMonth;

        $debts = $this->debts();
        $active = $debts->reject->isPaid();
        $totalDebt = $active->sum(fn (Debt $d) => $d->toBase($d->balance(), $this->usdRate));
        // Las deudas que empiezan más adelante no suman cuota en el mes actual
        $minPayments = (float) $active->reject->startsAfterThisMonth()
            ->sum(fn (Debt $d) => $d->toBase((float) $d->minimum_payment, $this->usdRate));

        $income = $this->incomeBase();

        $pending = $this->user->expenses()->whereNull('paid_on');
        $overdue = (clone $pending)->whereDate('spent_on', '<', today());

        return [
            'pending_total'      => (float) (clone $pending)->sum('amount_base'),
            'pending_count'      => (clone $pending)->count(),
            'overdue_total'      => (float) (clone $overdue)->sum('amount_base'),
            'overdue_count'      => (clone $overdue)->count(),
            'month_total'        => $current,
            'previous_total'     => $previous,
            'change_pct'         => $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null,
            'daily_average'      => $dailyAverage,
            'projection'         => $projection,
            'expense_count'      => $this->user->expenses()
                ->whereBetween('spent_on', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count(),
            'total_debt'         => $totalDebt,
            'active_debts'       => $active->count(),
            'min_payments'       => $minPayments,
            'income'             => $income,
            'debt_payment_ratio' => $income > 0 ? round($minPayments / $income * 100, 1) : null,
            'expense_ratio'      => $income > 0 ? round($projection / $income * 100, 1) : null,
            'free_cash'          => $income > 0 ? $income - $projection - $minPayments : null,
        ];
    }

    /** Totales de gasto por mes para los últimos N meses (incluye meses en cero). */
    public function monthlyTrend(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow($months - 1);

        $totals = $this->user->expenses()
            ->where('spent_on', '>=', $start)
            ->selectRaw("DATE_FORMAT(spent_on, '%Y-%m') as ym, SUM(amount_base) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $values = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $labels[] = ucfirst($month->locale('es')->translatedFormat('M Y'));
            $values[] = round((float) ($totals[$month->format('Y-m')] ?? 0), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** Gasto por categoría en un mes, con presupuesto asignado. */
    public function byCategory(Carbon $month): Collection
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $totals = $this->user->expenses()
            ->whereBetween('spent_on', [$from, $to])
            ->selectRaw('category_id, SUM(amount_base) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $rows = $this->user->categories()->get()->map(fn ($c) => [
            'name'   => $c->name,
            'color'  => $c->color,
            'total'  => round((float) ($totals[$c->id] ?? 0), 2),
            'budget' => $c->monthly_budget !== null ? (float) $c->monthly_budget : null,
        ]);

        $uncategorized = (float) ($totals[''] ?? 0);
        if ($uncategorized > 0) {
            $rows->push(['name' => 'Sin categoría', 'color' => '#A0AEC0', 'total' => round($uncategorized, 2), 'budget' => null]);
        }

        return $rows->sortByDesc('total')->values();
    }

    public function byPaymentMethod(Carbon $month): Collection
    {
        return $this->user->expenses()
            ->whereBetween('spent_on', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('payment_method, SUM(amount_base) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($r) => [
                'method' => \App\Models\Expense::PAYMENT_METHODS[$r->payment_method] ?? 'Sin especificar',
                'total'  => round((float) $r->total, 2),
            ]);
    }

    public function debts(): Collection
    {
        return $this->user->debts()->withSum('payments', 'amount')->orderBy('name')->get();
    }

    /**
     * Resumen agregado y anónimo que se envía a la IA.
     * No incluye nombre, email ni descripciones libres de gastos.
     */
    public function aiSnapshot(): array
    {
        $now = now();
        $summary = $this->summary();
        $trend = $this->monthlyTrend(12);

        $lastThreeMonths = collect(range(0, 2))->map(function ($i) use ($now) {
            $month = $now->copy()->subMonthsNoOverflow($i);

            return [
                'mes'                 => $month->format('Y-m'),
                'mes_en_curso'        => $i === 0,
                'total_gastado'       => round($this->monthTotal($month), 2),
                'gasto_por_categoria' => $this->byCategory($month)
                    ->filter(fn ($c) => $c['total'] > 0 || $c['budget'])
                    ->map(fn ($c) => [
                        'categoria'   => $c['name'],
                        'gastado'     => $c['total'],
                        'presupuesto' => $c['budget'],
                    ])->values()->all(),
            ];
        })->all();

        $usdShare = $this->user->expenses()
            ->whereBetween('spent_on', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->where('currency', 'USD')->sum('amount');

        return [
            'fecha_analisis'          => $now->toDateString(),
            'moneda_base'             => 'ARS (todos los totales están en pesos; lo cargado en dólares se convirtió)',
            'cotizacion_usd'          => $this->usdRate ? [
                'tipo'          => ExchangeRate::TYPES[$this->user->usd_rate_type] ?? $this->user->usd_rate_type,
                'pesos_por_usd' => $this->usdRate,
            ] : null,
            'gastos_en_usd_este_mes'  => round((float) $usdShare, 2),
            'ingresos_mensuales'      => $this->user->incomes()->get()->map(fn ($i) => [
                'nombre'   => $i->name,
                'moneda'   => $i->currency,
                'monto'    => (float) $i->amount,
                'en_pesos' => ExchangeRate::toBase((float) $i->amount, $i->currency, $this->usdRate),
            ])->all(),
            'dia_del_mes'             => $now->day,
            'dias_del_mes'            => $now->daysInMonth,
            'ingreso_mensual_total_en_pesos' => $summary['income'] ?: null,
            'proyeccion_gasto_mes'    => round($summary['projection'], 2),
            'promedio_diario'         => round($summary['daily_average'], 2),
            'variacion_vs_mes_previo' => $summary['change_pct'],
            'tendencia_12_meses'      => array_combine($trend['labels'], $trend['values']),
            'ultimos_3_meses'         => $lastThreeMonths,
            'medios_de_pago_mes'      => $this->byPaymentMethod($now)->all(),
            'deudas'                  => $this->debts()->map(fn (Debt $d) => [
                'tipo'              => $d->typeLabel(),
                'moneda'            => $d->currency,
                'saldo_pendiente'   => $d->balance(),
                'saldo_en_pesos'    => $d->toBase($d->balance(), $this->usdRate),
                'monto_original'    => (float) $d->principal,
                'tasa_anual_pct'    => (float) $d->interest_rate,
                'pago_minimo'       => (float) $d->minimum_payment,
                'dia_vencimiento'   => $d->due_day,
                'progreso_pago_pct' => $d->progress(),
                'cuota_del_mes'     => match ($d->installmentStatus()) {
                    'paid'    => 'pagada',
                    'pending' => 'pendiente',
                    'overdue' => 'vencida',
                    'not_started' => 'todavía no empezó (primera cuota más adelante)',
                    default   => $d->isPaid() ? 'deuda cancelada' : 'sin cuota fija',
                },
            ])->values()->all(),
            'gastos_pendientes_de_pago' => [
                'monto_total'     => round($summary['pending_total'], 2),
                'cantidad'        => $summary['pending_count'],
                'vencidos_monto'  => round($summary['overdue_total'], 2),
                'vencidos_cantidad' => $summary['overdue_count'],
            ],
            'total_deuda'             => round($summary['total_debt'], 2),
            'pagos_minimos_mensuales' => $summary['min_payments'],
            'ratio_deuda_ingreso_pct' => $summary['debt_payment_ratio'],
        ];
    }
}
