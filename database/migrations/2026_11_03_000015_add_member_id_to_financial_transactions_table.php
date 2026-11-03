<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreignId('member_id')
                ->nullable()
                ->after('category_id')
                ->constrained('members')
                ->nullOnDelete();

            $table->index(['space_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropIndex(['space_id', 'member_id']);
            $table->dropColumn('member_id');
        });
    }
};
