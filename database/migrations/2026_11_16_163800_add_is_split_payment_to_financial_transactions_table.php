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
        if (! Schema::hasColumn('financial_transactions', 'is_split_payment')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->boolean('is_split_payment')->default(false)->after('is_split')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('financial_transactions', 'is_split_payment')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropIndex(['is_split_payment']);
                $table->dropColumn('is_split_payment');
            });
        }
    }
};
