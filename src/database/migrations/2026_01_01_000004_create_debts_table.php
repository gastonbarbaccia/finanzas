<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('creditor', 100)->nullable();
            $table->string('type', 20);
            $table->decimal('principal', 14, 2);
            $table->decimal('interest_rate', 6, 2)->default(0);
            $table->decimal('minimum_payment', 14, 2)->default(0);
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->date('started_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('paid_on');
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['debt_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('debts');
    }
};
