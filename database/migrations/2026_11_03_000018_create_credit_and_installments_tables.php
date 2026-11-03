<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('credit_card');
            $table->bigInteger('credit_limit')->default(0);
            $table->unsignedTinyInteger('billing_cycle_day')->nullable();
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'archived_at']);
            $table->index(['space_id', 'name']);
        });

        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('credit_account_id')->constrained('credit_accounts')->cascadeOnDelete();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('name');
            $table->bigInteger('total_amount');
            $table->unsignedSmallInteger('tenor');
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->date('start_date');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'credit_account_id', 'status']);
        });

        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('installment_plan_id')->constrained('installment_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->bigInteger('principal_amount');
            $table->bigInteger('interest_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('total_amount');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->timestamps();

            $table->unique(['installment_plan_id', 'installment_number']);
            $table->index(['space_id', 'status', 'due_date']);
        });

        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('credit_account_id')->constrained('credit_accounts')->cascadeOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('installments')->nullOnDelete();
            $table->foreignId('source_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('transaction_id')->constrained('financial_transactions')->cascadeOnDelete();
            $table->bigInteger('principal_amount');
            $table->bigInteger('interest_amount')->default(0);
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('total_amount');
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'credit_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
        Schema::dropIfExists('installments');
        Schema::dropIfExists('installment_plans');
        Schema::dropIfExists('credit_accounts');
    }
};
