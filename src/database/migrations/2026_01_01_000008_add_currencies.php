<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte de pesos (ARS) y dólares (USD).
 * - Los gastos guardan su moneda, la cotización usada y el monto convertido a pesos (amount_base).
 * - Las deudas guardan su moneda; sus saldos se convierten con la cotización actual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('income_currency', 3)->default('ARS')->after('monthly_income');
            $table->string('usd_rate_type', 20)->default('oficial')->after('ai_provider');
            $table->decimal('usd_rate', 14, 4)->nullable()->after('usd_rate_type');
            $table->timestamp('usd_rate_updated_at')->nullable()->after('usd_rate');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('currency', 3)->default('ARS')->after('amount');
            $table->decimal('exchange_rate', 14, 4)->default(1)->after('currency');
            $table->decimal('amount_base', 16, 2)->default(0)->after('exchange_rate');
        });
        DB::table('expenses')->update(['amount_base' => DB::raw('amount')]);

        Schema::table('debts', function (Blueprint $table) {
            $table->string('currency', 3)->default('ARS')->after('type');
            $table->decimal('exchange_rate', 14, 4)->default(1)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('debts', fn (Blueprint $t) => $t->dropColumn(['currency', 'exchange_rate']));
        Schema::table('expenses', fn (Blueprint $t) => $t->dropColumn(['currency', 'exchange_rate', 'amount_base']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['income_currency', 'usd_rate_type', 'usd_rate', 'usd_rate_updated_at']));
    }
};
