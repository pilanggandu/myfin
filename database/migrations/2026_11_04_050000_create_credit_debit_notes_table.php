<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('credit_account_id')->constrained('credit_accounts')->cascadeOnDelete();
            $table->foreignId('installment_plan_id')->nullable()->constrained('installment_plans')->nullOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('installments')->nullOnDelete();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->string('type'); // 'credit_note' or 'debit_note'
            $table->bigInteger('amount');
            $table->date('note_date');
            $table->string('description');
            $table->timestamps();

            $table->index(['space_id', 'credit_account_id']);
            $table->index(['space_id', 'installment_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_debit_notes');
    }
};
