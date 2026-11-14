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
        if (Schema::hasTable('installment_plans')) {
            Schema::table('installment_plans', function (Blueprint $table) {
                $table->decimal('interest_rate', 8, 5)->default(0)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('installment_plans')) {
            Schema::table('installment_plans', function (Blueprint $table) {
                $table->decimal('interest_rate', 5, 2)->default(0)->change();
            });
        }
    }
};
