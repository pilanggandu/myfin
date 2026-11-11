<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->decimal('planned_amount', 18, 2);
            $table->decimal('actual_amount', 18, 2)->nullable();
            $table->date('target_date')->nullable();
            $table->string('priority', 16)->default('medium');
            $table->string('status', 24)->default('planned');
            $table->timestamp('realized_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['space_id', 'status', 'target_date']);
            $table->index(['space_id', 'priority']);
        });

        Schema::create('short_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('short_plan_id')->constrained('short_plans')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('estimated_amount', 18, 2)->default(0);
            $table->boolean('is_checked')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['short_plan_id', 'is_checked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_plan_items');
        Schema::dropIfExists('short_plans');
    }
};
