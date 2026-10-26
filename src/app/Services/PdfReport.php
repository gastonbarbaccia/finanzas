<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Expense;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Arma los datos de un período y genera el reporte en PDF con dompdf.
 */
class PdfReport
{
    public const PERIODS = [
        'this_month'    => 'Este mes',
        'last_month'    => 'Mes anterior',
        'last_3_months' => 'Últimos 3 meses',
        'this_year'     => 'Este año',
        'custom'        => 'Personalizado',
    ];

    public function __construct(private User $user)
    {
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public static function resolvePeriod(string $period, ?string $from = null, ?string $to = null): array
    {
        $today = today();

        return match ($period) {
            'last_month'    => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_3_months' => [$today->copy()->subMonthsNoOverflow(2)->startOfMonth(), $today->copy()->endOfMonth()],
            'this_year'     => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'custom'        => [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()],
            default         => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }

    public function data(Carbon $from, Carbon $to, array $options = []): array
    {
        $expenses = $this->user->expenses()
            ->with('category')
            ->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])
            ->orderBy('spent_on')->orderBy('id')
            ->get();

        $total = (float) $expenses->sum('amount_base');
        $pending = $expenses->whereNull('paid_on');

        // Días transcurridos del período (no cuenta días futuros)
        $lastDay = $to->copy()->min(today());
        $elapsedDays = $lastDay->lt($from) ? 0 : (int) $from->diffInDays($lastDay) + 1;

        // Período anterior de la misma duración, para comparar
        $length = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($length - 1);
        $previousTotal = (float) $this->user->expenses()
            ->whereBetween('spent_on', [$prevFrom->toDateString(), $prevTo->toDateString()])
            ->sum('amount_base');

        $months = max(1, (int) round($from->diffInDays($to) / 30.44));
        $usdRate = ExchangeRate::for($this->user);
        $income = (float) $this->user->incomes()->get()
            ->sum(fn ($i) => ExchangeRate::toBase((float) $i->amount, $i->currency, $usdRate));

        return [
            'user'        => $this->user,
            'from'        => $from,
            'to'          => $to,
            'generatedAt' => now(),
            'options'     => $options,
            'months'      => $months,
            'summary'     => [
                'total'          => $total,
                'count'          => $expenses->count(),
                'paid'           => $total - (float) $pending->sum('amount_base'),
                'pending'        => (float) $pending->sum('amount_base'),
                'usd_spent'      => (float) $expenses->where('currency', 'USD')->sum('amount'),
                'pending_count'  => $pending->count(),
                'daily_average'  => $elapsedDays > 0 ? $total / $elapsedDays : 0,
                'previous_total' => $previousTotal,
                'change_pct'     => $previousTotal > 0 ? round(($total - $previousTotal) / $previousTotal * 100, 1) : null,
                'income'         => $income * $months,
                'income_pct'     => $income > 0 ? round($total / ($income * $months) * 100, 1) : null,
            ],
            'categories'  => $this->byCategory($expenses, $months),
            'monthly'     => $this->byMonth($expenses, $from, $to),
            'methods'     => $expenses->groupBy(fn ($e) => $e->payment_method ?: '_')
                ->map(fn ($g, $key) => [
                    'label' => Expense::PAYMENT_METHODS[$key] ?? 'Sin especificar',
                    'total' => (float) $g->sum('amount_base'),
                ])->sortByDesc('total')->values(),
            'expenses'    => $expenses,
            'overdue'     => $this->user->expenses()->with('category')->whereNull('paid_on')
                ->whereDate('spent_on', '<', today())->orderBy('spent_on')->get(),
            'debts'       => $this->debts($from, $to, $usdRate),
            'usdRate'     => $usdRate,
            'usdRateType' => ExchangeRate::TYPES[$this->user->usd_rate_type] ?? null,
            'aiReport'    => ($options['ai'] ?? false) ? $this->user->aiRecommendations()->first() : null,
        ];
    }

    public function render(Carbon $from, Carbon $to, array $options = []): string
    {
        $html = view('reports.pdf', $this->data($from, $to, $options))->render();

        $cacheDir = storage_path('app/dompdf');
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $pdfOptions = new Options([
            'defaultFont'          => 'DejaVu Sans', // incluye tildes y ñ
            'isRemoteEnabled'      => false,          // no descarga recursos externos
            'isPhpEnabled'         => false,
            'isJavascriptEnabled'  => false,
            'chroot'               => base_path(),
            'fontCache'            => $cacheDir,
            'tempDir'              => $cacheDir,
        ]);

        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        // Numeración de páginas
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text(
            $canvas->get_width() - 110, $canvas->get_height() - 33,
            'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 7.5, [0.36, 0.40, 0.45]
        );

        return $dompdf->output();
    }

    public static function filename(Carbon $from, Carbon $to): string
    {
        $period = $from->isSameMonth($to) && $from->day === 1 && $to->isLastOfMonth()
            ? $from->format('Y-m')
            : $from->format('Y-m-d') . '_' . $to->format('Y-m-d');

        return 'reporte-finanzas-' . $period . '.pdf';
    }

    private function byCategory($expenses, int $months)
    {
        $categories = $this->user->categories()->get()->keyBy('id');
        $total = max(0.01, (float) $expenses->sum('amount_base'));

        return $expenses->groupBy(fn ($e) => $e->category_id ?? 0)
            ->map(function ($group, $id) use ($categories, $total, $months) {
                $category = $categories[$id] ?? null;
                $sum = (float) $group->sum('amount_base');
                $budget = $category?->monthly_budget !== null ? (float) $category->monthly_budget * $months : null;

                return [
                    'name'       => $category?->name ?? 'Sin categoría',
                    'color'      => $category?->color ?? '#A0AEC0',
                    'total'      => $sum,
                    'count'      => $group->count(),
                    'share'      => round($sum / $total * 100, 1),
                    'budget'     => $budget,
                    'budget_pct' => $budget ? round($sum / $budget * 100) : null,
                ];
            })->sortByDesc('total')->values();
    }

    private function byMonth($expenses, Carbon $from, Carbon $to)
    {
        if ($from->isSameMonth($to)) {
            return collect();
        }

        $totals = $expenses->groupBy(fn ($e) => $e->spent_on->format('Y-m'))->map->sum('amount_base');
        $rows = collect();
        for ($m = $from->copy()->startOfMonth(); $m->lte($to); $m->addMonthNoOverflow()) {
            $rows->push([
                'label' => Str::ucfirst($m->translatedFormat('F Y')),
                'total' => (float) ($totals[$m->format('Y-m')] ?? 0),
            ]);
        }

        return $rows;
    }

    private function debts(Carbon $from, Carbon $to, ?float $usdRate)
    {
        return $this->user->debts()->withSum('payments', 'amount')->orderBy('name')->get()
            ->map(fn (Debt $d) => [
                'debt'           => $d,
                'balance'        => $d->balance(),
                'balance_base'   => $d->toBase($d->balance(), $usdRate),
                'paid_in_period' => $paid = (float) $d->payments()
                    ->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->sum('amount'),
                'paid_base'      => $d->toBase($paid, $usdRate),
                'payments'       => $d->payments()
                    ->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->get(),
                'status'         => $d->installmentStatus(),
            ]);
    }
}
