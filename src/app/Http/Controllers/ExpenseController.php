<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month'    => ['nullable', 'date_format:Y-m'],
            'category' => ['nullable', 'integer'],
            'q'        => ['nullable', 'string', 'max:100'],
            'status'   => ['nullable', Rule::in(['paid', 'pending', 'overdue'])],
        ]);

        $month = isset($filters['month'])
            ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
            : now()->startOfMonth();

        $query = $request->user()->expenses()
            ->with('category')
            ->whereBetween('spent_on', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('description', 'like', '%' . addcslashes($term, '%_\\') . '%'))
            ->when($filters['status'] ?? null, fn ($q, $status) => match ($status) {
                'paid'    => $q->whereNotNull('paid_on'),
                'pending' => $q->whereNull('paid_on'),
                'overdue' => $q->whereNull('paid_on')->whereDate('spent_on', '<', today()),
            });

        $total = (clone $query)->sum('amount_base');
        $pendingTotal = (clone $query)->whereNull('paid_on')->sum('amount_base');
        $usdTotal = (clone $query)->where('currency', 'USD')->sum('amount');
        $expenses = $query->latest('spent_on')->latest('id')->paginate(20)->withQueryString();

        return view('expenses.index', [
            'expenses'   => $expenses,
            'total'        => $total,
            'pendingTotal' => $pendingTotal,
            'usdTotal'     => $usdTotal,
            'month'        => $month,
            'categories' => $request->user()->categories()->get(),
            'filters'    => $filters,
        ]);
    }

    public function create(Request $request)
    {
        return view('expenses.form', [
            'expense'    => new Expense(['spent_on' => now(), 'currency' => 'ARS']),
            'categories' => $request->user()->categories()->get(),
            'usdRate'    => ExchangeRate::for($request->user()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['paid_on'] = $request->boolean('is_paid') ? $data['spent_on'] : null;

        $request->user()->expenses()->create($data);

        return redirect()->route('expenses.index', ['month' => substr($data['spent_on'], 0, 7)])
            ->with('success', $data['paid_on'] ? 'Gasto guardado.' : 'Gasto guardado como pendiente de pago.');
    }

    public function edit(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);

        return view('expenses.form', [
            'expense'    => $expense,
            'categories' => $request->user()->categories()->get(),
            'usdRate'    => ExchangeRate::for($request->user()),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);

        $data = $this->validated($request);
        $data['paid_on'] = $request->boolean('is_paid') ? ($expense->paid_on ?? today()) : null;
        $expense->update($data);

        return redirect()->route('expenses.index', ['month' => $expense->spent_on->format('Y-m')])
            ->with('success', 'Gasto actualizado.');
    }

    /** Marca un gasto como pagado (hoy) o lo vuelve a pendiente. */
    public function togglePaid(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);
        $expense->update(['paid_on' => $expense->isPaid() ? null : today()]);

        return back()->with('success', $expense->isPaid() ? 'Gasto marcado como pagado.' : 'Gasto marcado como pendiente.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense);
        $expense->delete();

        return back()->with('success', 'Gasto eliminado.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'currency'       => ['required', Rule::in(array_keys(config('finance.currencies')))],
            'exchange_rate'  => ['nullable', 'required_if:currency,USD', 'numeric', 'min:0.0001', 'max:99999999'],
            'spent_on'       => ['required', 'date', 'after:2000-01-01', 'before:2100-01-01'],
            'description'    => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'is_paid'        => ['nullable', 'boolean'],
            'category_id'    => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->where('user_id', $request->user()->id),
            ],
        ]);
        unset($data['is_paid']);
        if ($data['currency'] !== 'USD') {
            $data['exchange_rate'] = 1;
        }

        return $data;
    }

    private function authorizeOwner(Request $request, Expense $expense): void
    {
        abort_unless($expense->user_id === $request->user()->id, 404);
    }
}
