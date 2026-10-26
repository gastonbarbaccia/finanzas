<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;

class DebtPaymentController extends Controller
{
    public function store(Request $request, Debt $debt)
    {
        abort_unless($debt->user_id === $request->user()->id, 404);

        $balance = $debt->balance();
        $data = $request->validate([
            'amount'  => ['required', 'numeric', 'min:0.01', 'max:' . max(0.01, $balance)],
            'paid_on' => ['required', 'date', 'after:2000-01-01', 'before:2100-01-01'],
            'note'    => ['nullable', 'string', 'max:255'],
        ], [
            'amount.max' => 'El pago no puede superar el saldo pendiente.',
        ]);

        $debt->payments()->create($data);

        return back()->with('success', 'Pago registrado.');
    }

    /** Registra un pago por todo el saldo y deja la deuda cancelada. */
    public function settle(Request $request, Debt $debt)
    {
        abort_unless($debt->user_id === $request->user()->id, 404);

        $balance = $debt->balance();
        if ($balance <= 0) {
            return back()->with('error', 'Esta deuda ya está cancelada.');
        }

        $debt->payments()->create([
            'amount'  => $balance,
            'paid_on' => today(),
            'note'    => 'Cancelación total',
        ]);

        return redirect()->route('debts.index')->with('success', "Deuda \"{$debt->name}\" cancelada.");
    }

    public function destroy(Request $request, Debt $debt, DebtPayment $payment)
    {
        abort_unless($debt->user_id === $request->user()->id && $payment->debt_id === $debt->id, 404);
        $payment->delete();

        return back()->with('success', 'Pago eliminado.');
    }
}
