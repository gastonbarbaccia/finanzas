<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Varios ingresos mensuales por usuario, cada uno en su moneda.
 * El ingreso único que había en Ajustes se pasa a esta tabla como "Ingreso principal".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('ARS');
            $table->timestamps();
        });

        $now = now();
        DB::table('users')->where('monthly_income', '>', 0)->orderBy('id')
            ->each(function ($user) use ($now) {
                DB::table('incomes')->insert([
                    'user_id'    => $user->id,
                    'name'       => 'Ingreso principal',
                    'amount'     => $user->monthly_income,
                    'currency'   => $user->income_currency ?? 'ARS',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
