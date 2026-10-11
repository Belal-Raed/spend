<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Expenses, income and debts share one table. A payment against a debt is
     * an expense or income row whose `settles_id` points at that debt.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->string('counterparty')->nullable();
            $table->string('debt_direction')->nullable();
            $table->date('settled_on')->nullable();
            $table->foreignId('settles_id')->nullable()->constrained('transactions')->cascadeOnDelete();
            $table->date('occurred_on');
            $table->text('raw_text')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'occurred_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
