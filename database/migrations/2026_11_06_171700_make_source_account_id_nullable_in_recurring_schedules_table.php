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
                $table->unsignedBigInteger('source_account_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recurring_schedules')) {
            Schema::table('recurring_schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('source_account_id')->nullable(false)->change();
            });
        }
    }
};
