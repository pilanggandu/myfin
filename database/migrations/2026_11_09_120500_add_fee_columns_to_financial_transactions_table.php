<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->decimal('fee_amount', 18, 2)->default(0)->after('category_id');
            $table->foreignId('fee_category_id')->nullable()->after('fee_amount')->constrained('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_category_id');
            $table->dropColumn('fee_amount');
        });
    }
};
