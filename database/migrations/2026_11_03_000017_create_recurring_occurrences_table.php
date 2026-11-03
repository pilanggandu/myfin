<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_schedule_id')->constrained('recurring_schedules')->cascadeOnDelete();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->date('due_date');
            $table->foreignId('transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('amount')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['recurring_schedule_id', 'due_date']);
            $table->index(['space_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_occurrences');
    }
};
