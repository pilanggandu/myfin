<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('total_limit', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'name']);
        });

        Schema::table('credit_accounts', function (Blueprint $table) {
            $table->foreignId('credit_facility_id')
                ->nullable()
                ->after('account_id')
                ->constrained('credit_facilities')
                ->nullOnDelete();
            $table->decimal('sub_limit', 18, 2)
                ->nullable()
                ->after('credit_limit');

            $table->index(['space_id', 'credit_facility_id']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_accounts', function (Blueprint $table) {
            $table->dropForeign(['credit_facility_id']);
            $table->dropColumn(['credit_facility_id', 'sub_limit']);
        });

        Schema::dropIfExists('credit_facilities');
    }
};
