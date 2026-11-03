<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->bigInteger('opening_balance')->default(0);
            $table->date('opened_on');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'archived_at']);
            $table->index(['space_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
