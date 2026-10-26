<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Services\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $debts = $request->user()->debts()->withSum('payments', 'amount')->orderBy('name')->get();
        [$paid, $active] = $debts->partition->isPaid();
        $rate = ExchangeRate::for($request->user());

        return view('debts.index', [
            'active'       => $active->values(),
            'paid'         => $paid->values(),
            'totalBalance' => $active->sum(fn ($d) => $d->toBase($d->balance(), $rate)),
            'totalMinimum' => $active->reject->startsAfterThisMonth()->sum(fn ($d) => $d->toBase((float) $d->minimum_payment, $rate)),
            'usdRate'      => $rate,
            'statuses'     => $active->mapWithKeys(fn ($d) => [$d->id => $d->installmentStatus()]),
        ]);
    }

    public function create()
    {
        return view('debts.form', [
            'debt'    => new Debt(['type' => 'tarjeta', 'currency' => 'ARS']),
            'usdRate' => ExchangeRate::for(request()->user()),
        ]);
    }

    public function store(Request $request)
    {
        $debt = $request->user()->debts()->create($this->validated($request));

        return redirect()->route('debts.show', $debt)->with('success', 'Deuda registrada.');
    }

    public function show(Request $request, Debt $debt)
    {
        $this->authorizeOwner($request, $debt);
        $debt->loadSum('payments', 'amount');

        return view('debts.show', [
            'usdRate'  => ExchangeRate::for($request->user()),
            'debt'     => $debt,
            'payments' => $debt->payments()->get(),
        ]);
    }

    public function edit(Request $request, Debt $debt)
    {
        $this->authorizeOwner($request, $debt);

        return view('debts.form', ['debt' => $debt, 'usdRate' => ExchangeRate::for($request->user())]);
    }

    public function update(Request $request, Debt $debt)
    {
        $this->authorizeOwner($request, $debt);
        $debt->update($this->validated($request));

        return redirect()->route('debts.show', $debt)->with('success', 'Deuda actualizada.');
    }

    public function destroy(Request $request, Debt $debt)
    {
        $this->authorizeOwner($request, $debt);
        $debt->delete();

        return redirect()->route('debts.index')->with('success', 'Deuda eliminada.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'creditor'        => ['nullable', 'string', 'max:100'],
            'type'            => ['required', Rule::in(array_keys(Debt::TYPES))],
            'currency'        => ['required', Rule::in(array_keys(config('finance.currencies')))],
            'exchange_rate'   => ['nullable', 'required_if:currency,USD', 'numeric', 'min:0.0001', 'max:99999999'],
            'principal'       => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'interest_rate'   => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'minimum_payment' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'due_day'         => ['nullable', 'integer', 'between:1,31'],
            'started_on'      => ['nullable', 'date'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ]);

        $data['interest_rate'] ??= 0;
        $data['exchange_rate'] = $data['currency'] === 'USD' ? $data['exchange_rate'] : 1;
        $data['minimum_payment'] ??= 0;

        return $data;
    }

    private function authorizeOwner(Request $request, Debt $debt): void
    {
        abort_unless($debt->user_id === $request->user()->id, 404);
    }
}
