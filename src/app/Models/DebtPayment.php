<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    protected $fillable = ['amount', 'paid_on', 'note'];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'paid_on' => 'date',
        ];
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
