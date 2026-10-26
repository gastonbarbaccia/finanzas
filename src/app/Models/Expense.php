<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const PAYMENT_METHODS = [
        'efectivo'      => 'Efectivo',
        'debito'        => 'Tarjeta de débito',
        'credito'       => 'Tarjeta de crédito',
        'transferencia' => 'Transferencia',
        'billetera'     => 'Billetera virtual',
    ];

    protected $fillable = [
        'category_id', 'amount', 'currency', 'exchange_rate', 'description', 'spent_on', 'paid_on', 'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'amount_base'   => 'decimal:2',
            'spent_on' => 'date',
            'paid_on'  => 'date',
        ];
    }

    protected static function booted(): void
    {
        // amount_base = monto en pesos, usado en todos los totales y gráficos
        static::saving(function (Expense $expense) {
            $expense->currency = $expense->currency ?: 'ARS';
            if ($expense->currency !== 'USD') {
                $expense->exchange_rate = 1;
            }
            $expense->amount_base = round((float) $expense->amount * (float) $expense->exchange_rate, 2);
        });
    }

    public function isUsd(): bool
    {
        return $this->currency === 'USD';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePending($query)
    {
        return $query->whereNull('paid_on');
    }

    public function isPaid(): bool
    {
        return $this->paid_on !== null;
    }

    /** Pendiente y con la fecha de vencimiento ya pasada. */
    public function isOverdue(): bool
    {
        return ! $this->isPaid() && $this->spent_on->lt(today());
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? '—';
    }
}
