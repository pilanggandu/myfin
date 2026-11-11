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
        Schema::table('recurring_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('recurring_schedules', 'credit_account_id')) {
                $table->foreignId('credit_account_id')
                    ->nullable()
                    ->after('destination_account_id')
                    ->constrained('credit_accounts')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('recurring_schedules', 'credit_account_id')) {
                $table->dropConstrainedForeignId('credit_account_id');
            }
        });
    }
};
