<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->string('type_criterion', 20)->default('all');
            $table->string('keyword')->nullable();
            $table->string('keyword_operator', 20)->default('contains');
            $table->string('amount_operator', 25)->default('any');
            $table->decimal('amount_min', 18, 2)->nullable();
            $table->decimal('amount_max', 18, 2)->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('action_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('action_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('action_description')->nullable();
            $table->text('action_note')->nullable();
            $table->boolean('stop_processing')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['space_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_rules');
    }
};
