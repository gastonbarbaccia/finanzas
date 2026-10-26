<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo para probar gráficos y la IA.
 * Uso: docker compose exec app php artisan db:seed --class=DemoSeeder
 * Usuario: demo@example.com / demo1234
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Usuario Demo', 'password' => 'demo1234',
             'usd_rate_type' => 'oficial', 'usd_rate' => 1450, 'usd_rate_updated_at' => now()]
        );
        $user->createDefaultCategories();
        $user->incomes()->delete();
        $user->incomes()->createMany([
            ['name' => 'Sueldo', 'amount' => 650000, 'currency' => 'ARS'],
            ['name' => 'Cliente del exterior', 'amount' => 400, 'currency' => 'USD'],
        ]);
        $user->expenses()->delete();
        $user->debts()->delete();

        $plan = [
            'Alimentación'    => [[180000, 260000], 10, 'Supermercado'],
            'Vivienda'        => [[250000, 250000], 1, 'Alquiler'],
            'Servicios'       => [[45000, 70000], 4, 'Luz, gas, internet'],
            'Transporte'      => [[40000, 75000], 8, 'Combustible y transporte'],
            'Salud'           => [[20000, 60000], 2, 'Farmacia'],
            'Entretenimiento' => [[30000, 110000], 5, 'Salidas'],
            'Ropa'            => [[0, 80000], 1, 'Indumentaria'],
        ];
        $budgets = ['Alimentación' => 220000, 'Entretenimiento' => 60000, 'Transporte' => 60000];
        $methods = ['efectivo', 'debito', 'credito', 'transferencia', 'billetera'];
        $categories = $user->categories()->get()->keyBy('name');

        foreach ($budgets as $name => $budget) {
            $categories[$name]?->update(['monthly_budget' => $budget]);
        }

        for ($m = 5; $m >= 0; $m--) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($m);
            $lastDay = $m === 0 ? now()->day : $month->daysInMonth;
            $factor = $m === 0 ? $lastDay / $month->daysInMonth : 1;

            foreach ($plan as $name => [[$min, $max], $count, $description]) {
                $total = mt_rand($min, $max) * $factor * (1 + (5 - $m) * 0.03);
                if ($total <= 0) {
                    continue;
                }
                $count = max(1, (int) round($count * $factor));
                for ($i = 0; $i < $count; $i++) {
                    $user->expenses()->create([
                        'category_id'    => $categories[$name]->id,
                        'amount'         => round($total / $count, 2),
                        'description'    => $description,
                        'spent_on'       => $month->copy()->day(mt_rand(1, $lastDay)),
                        'payment_method' => $methods[array_rand($methods)],
                    ]);
                }
            }
        }

        // Los gastos del mes con fecha futura y dos facturas quedan pendientes
        $user->expenses()->whereDate('spent_on', '>', today())->update(['paid_on' => null]);
        $user->expenses()->whereNotNull('spent_on')->whereNull('paid_on')
            ->whereDate('spent_on', '<=', today())->update(['paid_on' => \Illuminate\Support\Facades\DB::raw('spent_on')]);
        $user->expenses()->create([
            'category_id' => $categories['Servicios']->id, 'amount' => 38500,
            'description' => 'Factura de luz', 'spent_on' => today()->addDays(6), 'paid_on' => null,
        ]);
        $user->expenses()->create([
            'category_id' => $categories['Servicios']->id, 'amount' => 24900,
            'description' => 'Internet', 'spent_on' => today()->subDays(2), 'paid_on' => null,
        ]);

        // Un gasto en dólares
        $user->expenses()->create([
            'category_id' => $categories['Entretenimiento']->id, 'amount' => 12.99, 'currency' => 'USD',
            'exchange_rate' => 1450, 'description' => 'Suscripción de streaming',
            'spent_on' => today()->startOfMonth()->addDays(2), 'paid_on' => today()->startOfMonth()->addDays(2),
            'payment_method' => 'credito',
        ]);

        $card = $user->debts()->create([
            'name' => 'Tarjeta Visa', 'creditor' => 'Banco', 'type' => 'tarjeta',
            'principal' => 420000, 'interest_rate' => 85, 'minimum_payment' => 60000, 'due_day' => 10,
        ]);
        $card->payments()->create(['amount' => 60000, 'paid_on' => now()->subMonthNoOverflow()->day(9)]);

        $loan = $user->debts()->create([
            'name' => 'Préstamo personal', 'creditor' => 'Financiera', 'type' => 'prestamo',
            'principal' => 1200000, 'interest_rate' => 60, 'minimum_payment' => 95000, 'due_day' => 5,
            'started_on' => now()->subMonthsNoOverflow(4)->startOfMonth(),
        ]);
        // Una deuda en dólares
        $usdDebt = $user->debts()->create([
            'name' => 'Préstamo familiar', 'creditor' => 'Familia', 'type' => 'personal',
            'currency' => 'USD', 'exchange_rate' => 1450,
            'principal' => 1500, 'minimum_payment' => 150, 'due_day' => 20,
        ]);
        $usdDebt->payments()->create(['amount' => 150, 'paid_on' => now()->subMonthNoOverflow()->day(18)]);

        for ($i = 4; $i >= 1; $i--) {
            $loan->payments()->create(['amount' => 95000, 'paid_on' => now()->subMonthsNoOverflow($i)->day(5)]);
        }
    }
}
