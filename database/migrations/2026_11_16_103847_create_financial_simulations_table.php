<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('scenario_type', 32); // asset_purchase, loan_installment, saving_target
            $table->text('description')->nullable();
            $table->decimal('baseline_income', 18, 2)->default(0);
            $table->decimal('baseline_expense', 18, 2)->default(0);
            $table->decimal('baseline_debt', 18, 2)->default(0);
            $table->decimal('baseline_liquid_cash', 18, 2)->default(0);
            $table->json('parameters');
            $table->json('results');
            $table->boolean('is_starred')->default(false);
            $table->timestamps();

            $table->index(['space_id', 'scenario_type']);
            $table->index(['space_id', 'is_starred']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_simulations');
    }
};
