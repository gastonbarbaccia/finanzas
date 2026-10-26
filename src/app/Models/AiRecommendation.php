<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    protected $fillable = ['provider', 'model', 'content'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
