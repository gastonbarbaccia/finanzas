<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Services\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $rate = ExchangeRate::for($request->user());
        $incomes = $request->user()->incomes()->get();

        return view('incomes.index', [
            'incomes' => $incomes,
            'usdRate' => $rate,
            'total'   => $incomes->sum(fn (Income $i) => ExchangeRate::toBase((float) $i->amount, $i->currency, $rate)),
            'byCurrency' => $incomes->groupBy('currency')->map->sum('amount'),
        ]);
    }

    public function store(Request $request)
    {
        $request->user()->incomes()->create($this->validated($request));

        return back()->with('success', 'Ingreso agregado.');
    }

    public function update(Request $request, Income $income)
    {
        $this->authorizeOwner($request, $income);
        $income->update($this->validated($request));

        return back()->with('success', 'Ingreso actualizado.');
    }

    public function destroy(Request $request, Income $income)
    {
        $this->authorizeOwner($request, $income);
        $income->delete();

        return back()->with('success', 'Ingreso eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:60'],
            'amount'   => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'currency' => ['required', Rule::in(array_keys(config('finance.currencies')))],
        ]);
    }

    private function authorizeOwner(Request $request, Income $income): void
    {
        abort_unless($income->user_id === $request->user()->id, 404);
    }
}
