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
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('personal'); // personal, shared
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->timestamps();

            $table->index(['owner_id', 'type']);
        });

        Schema::create('space_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member'); // owner, member
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();

            $table->unique(['space_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_members');
        Schema::dropIfExists('spaces');
    }
};
