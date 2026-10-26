<?php

namespace App\Http\Controllers;

use App\Services\FinanceMetrics;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $metrics = new FinanceMetrics($user);
        $categories = $metrics->byCategory(now());
        $debts = $metrics->debts()->reject->isPaid()->values();

        return view('dashboard', [
            'summary'       => $metrics->summary(),
            'trend'         => $metrics->monthlyTrend(12),
            'categories'    => $categories,
            'budgeted'      => $categories->filter(fn ($c) => $c['budget'])->values(),
            'methods'       => $metrics->byPaymentMethod(now()),
            'debts'         => $debts,
            // Datos ya preparados para Chart.js
            'chartCategories' => $categories->where('total', '>', 0)->values(),
            // Saldos convertidos a pesos para poder compararlos en un mismo gráfico
            'chartDebts'      => $debts->map(fn ($d) => [
                'name'    => $d->name . ($d->isUsd() ? ' (USD)' : ''),
                'paid'    => $d->toBase($d->paidAmount(), $metrics->usdRate),
                'balance' => $d->toBase($d->balance(), $metrics->usdRate),
            ])->values(),
            'usdRate'         => $metrics->usdRate,
            'installments'  => $debts->map(fn ($d) => ['debt' => $d, 'status' => $d->installmentStatus()])
                ->filter(fn ($i) => in_array($i['status'], ['pending', 'overdue']))
                ->sortBy(fn ($i) => $i['debt']->dueDateThisMonth()?->timestamp ?? PHP_INT_MAX)
                ->values(),
            'pending'       => $user->expenses()->pending()->with('category')->orderBy('spent_on')->take(8)->get(),
            'recent'        => $user->expenses()->with('category')->latest('spent_on')->latest('id')->take(6)->get(),
        ]);
    }
}
