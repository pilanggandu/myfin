<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planned_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('actual_amount', 18, 2)->nullable();
            $table->date('expected_date');
            $table->string('status', 24)->default('planned');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['space_id', 'status', 'expected_date']);
            $table->index(['space_id', 'expected_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planned_incomes');
    }
};
