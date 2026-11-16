<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'rollover_enabled')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('rollover_enabled')->default(false)->after('type');
                $table->string('rollover_mode', 30)->default('all')->after('rollover_enabled');
                $table->unsignedBigInteger('rollover_cap')->nullable()->after('rollover_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'rollover_enabled')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn(['rollover_enabled', 'rollover_mode', 'rollover_cap']);
            });
        }
    }
};
