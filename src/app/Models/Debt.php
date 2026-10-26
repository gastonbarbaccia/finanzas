<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    public const TYPES = [
        'tarjeta'  => 'Tarjeta de crédito',
        'prestamo' => 'Préstamo',
        'hipoteca' => 'Hipoteca',
        'personal' => 'Deuda personal',
        'otro'     => 'Otro',
    ];

    protected $fillable = [
        'name', 'creditor', 'type', 'currency', 'exchange_rate', 'principal', 'interest_rate',
        'minimum_payment', 'due_day', 'started_on', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal'       => 'decimal:2',
            'exchange_rate'   => 'decimal:4',
            'interest_rate'   => 'decimal:2',
            'minimum_payment' => 'decimal:2',
            'due_day'         => 'integer',
            'started_on'      => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class)->orderByDesc('paid_on')->orderByDesc('id');
    }

    /** Total pagado (usa withSum si fue precargado). */
    public function paidAmount(): float
    {
        return (float) ($this->payments_sum_amount ?? $this->payments()->sum('amount'));
    }

    public function balance(): float
    {
        return max(0, round((float) $this->principal - $this->paidAmount(), 2));
    }

    public function progress(): float
    {
        $principal = (float) $this->principal;

        return $principal > 0 ? min(100, round($this->paidAmount() / $principal * 100, 1)) : 100;
    }

    public function isPaid(): bool
    {
        return $this->balance() <= 0;
    }

    /** Total pagado en el mes actual. */
    public function paidThisMonth(): float
    {
        return (float) $this->payments()
            ->whereBetween('paid_on', [today()->startOfMonth(), today()->endOfMonth()])
            ->sum('amount');
    }

    /** Fecha de vencimiento de la cuota de este mes (si tiene día de vencimiento). */
    public function dueDateThisMonth(): ?\Illuminate\Support\Carbon
    {
        if (! $this->due_day) {
            return null;
        }
        $month = today()->startOfMonth();

        return $month->day(min($this->due_day, $month->daysInMonth));
    }

    /**
     * Primera fecha de vencimiento según la fecha de inicio: el día de vencimiento
     * del mes de inicio, o del mes siguiente si ese día es anterior al inicio.
     */
    public function firstDueDate(): ?\Illuminate\Support\Carbon
    {
        if (! $this->started_on || ! $this->due_day) {
            return null;
        }

        $month = $this->started_on->copy()->startOfMonth();
        $date = $month->copy()->day(min($this->due_day, $month->daysInMonth));

        if ($date->lt($this->started_on)) {
            $month->addMonthNoOverflow();
            $date = $month->copy()->day(min($this->due_day, $month->daysInMonth));
        }

        return $date;
    }

    /** La deuda todavía no tiene cuotas que pagar este mes (empieza más adelante). */
    public function startsAfterThisMonth(): bool
    {
        $endOfMonth = today()->endOfMonth();
        $first = $this->firstDueDate();

        return ($this->started_on && $this->started_on->gt($endOfMonth))
            || ($first && $first->gt($endOfMonth));
    }

    /**
     * Estado de la cuota del mes: 'paid', 'pending', 'overdue', 'not_started' o null
     * (deuda cancelada o sin pago mínimo ni vencimiento cargados).
     */
    public function installmentStatus(): ?string
    {
        if ($this->isPaid() || ((float) $this->minimum_payment <= 0 && ! $this->due_day)) {
            return null;
        }

        if ($this->startsAfterThisMonth()) {
            return 'not_started';
        }

        $paid = $this->paidThisMonth();
        $target = (float) $this->minimum_payment > 0
            ? min((float) $this->minimum_payment, $this->balance() + $paid)
            : 0.01;

        if ($paid >= $target) {
            return 'paid';
        }

        $due = $this->dueDateThisMonth();

        return $due && $due->lt(today()) ? 'overdue' : 'pending';
    }

    public function lastPaymentDate(): ?\Illuminate\Support\Carbon
    {
        return $this->payments()->value('paid_on');
    }

    public function isUsd(): bool
    {
        return $this->currency === 'USD';
    }

    /**
     * Convierte un monto de esta deuda a pesos con la cotización actual
     * (o la de cuando se cargó la deuda, si no hay cotización actual).
     */
    public function toBase(float $amount, ?float $currentRate): float
    {
        if (! $this->isUsd()) {
            return $amount;
        }

        return round($amount * (float) ($currentRate ?: $this->exchange_rate), 2);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
