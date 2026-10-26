<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // null = pendiente de pago. spent_on pasa a ser fecha o vencimiento.
            $table->date('paid_on')->nullable()->after('spent_on');
            $table->index(['user_id', 'paid_on']);
        });

        // Los gastos cargados antes de esta versión se consideran pagados
        DB::table('expenses')->whereNull('paid_on')->update(['paid_on' => DB::raw('spent_on')]);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'paid_on']);
            $table->dropColumn('paid_on');
        });
    }
};
