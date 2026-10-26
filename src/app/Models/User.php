<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'monthly_income', 'income_currency', 'ai_provider',
        'usd_rate_type', 'usd_rate', 'usd_rate_updated_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'monthly_income'      => 'decimal:2',
            'usd_rate'            => 'decimal:4',
            'usd_rate_updated_at' => 'datetime',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('name');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function aiRecommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class)->latest();
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class)->orderBy('id');
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class)->latest('updated_at');
    }

    public function createDefaultCategories(): void
    {
        foreach (config('finance.default_categories') as $category) {
            $this->categories()->firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
