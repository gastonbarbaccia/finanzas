<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('monthly_income', 14, 2)->nullable()->after('password');
            $table->string('ai_provider', 20)->nullable()->after('monthly_income');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['monthly_income', 'ai_provider']);
        });
    }
};
