<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('source_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('frequency');
            $table->unsignedTinyInteger('anchor_day')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date');
            $table->unsignedBigInteger('amount')->nullable();
            $table->boolean('is_variable_amount')->default(false);
            $table->string('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['space_id', 'status', 'next_due_date']);
            $table->index(['space_id', 'type']);
            $table->index(['space_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_schedules');
    }
};
