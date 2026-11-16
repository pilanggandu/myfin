<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('financial_transactions', 'is_split')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->boolean('is_split')->default(false)->after('status')->index();
            });
        }

        Schema::create('transaction_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('financial_transactions')->cascadeOnDelete();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'category_id']);
            $table->index(['transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_splits');

        if (Schema::hasColumn('financial_transactions', 'is_split')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropIndex(['is_split']);
                $table->dropColumn('is_split');
            });
        }
    }
};
