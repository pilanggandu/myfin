<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financial_transactions') && ! Schema::hasColumn('financial_transactions', 'notes')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('financial_transactions') && Schema::hasColumn('financial_transactions', 'notes')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};
