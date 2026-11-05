<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->decimal('target_amount', 18, 2);
            $table->decimal('current_amount', 18, 2)->default(0);
            $table->date('start_date');
            $table->date('target_date')->nullable();
            $table->string('icon', 64)->default('🎯');
            $table->string('color', 32)->default('#174C40');
            $table->string('status', 32)->default('active'); // active, achieved, paused, cancelled
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['space_id', 'status']);
            $table->index(['space_id', 'target_date']);
        });

        Schema::create('goal_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->string('type', 32)->default('deposit'); // deposit, withdrawal
            $table->decimal('amount', 18, 2);
            $table->date('contribution_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['space_id', 'goal_id']);
            $table->index(['goal_id', 'contribution_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_contributions');
        Schema::dropIfExists('goals');
    }
};
