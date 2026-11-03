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
        Schema::table('spaces', function (Blueprint $table) {
            $table->unsignedBigInteger('personal_owner_id')
                ->nullable()
                ->virtualAs("CASE WHEN type = 'personal' THEN owner_id ELSE NULL END");

            $table->unique('personal_owner_id', 'spaces_personal_owner_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->dropUnique('spaces_personal_owner_id_unique');
            $table->dropColumn('personal_owner_id');
        });
    }
};
