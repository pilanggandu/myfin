<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recurring_schedules')) {
            Schema::table('recurring_schedules', function (Blueprint $table) {
                $table->decimal('amount', 18, 2)->nullable()->change();
            });
        }

        if (Schema::hasTable('recurring_occurrences')) {
            Schema::table('recurring_occurrences', function (Blueprint $table) {
                $table->decimal('amount', 18, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recurring_schedules')) {
            Schema::table('recurring_schedules', function (Blueprint $table) {
                $table->decimal('amount', 18, 2)->nullable()->change();
            });
        }

        if (Schema::hasTable('recurring_occurrences')) {
            Schema::table('recurring_occurrences', function (Blueprint $table) {
                $table->decimal('amount', 18, 2)->nullable()->change();
            });
        }
    }
};
