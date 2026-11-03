<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 25)->nullable()->after('email');
            $table->string('whatsapp_api_key', 100)->nullable()->after('phone');
            $table->string('whatsapp_gateway_url', 255)->nullable()->after('whatsapp_api_key');
            $table->boolean('email_notifications_enabled')->default(true)->after('whatsapp_gateway_url');
            $table->boolean('whatsapp_notifications_enabled')->default(false)->after('email_notifications_enabled');
            $table->boolean('daily_reminder_enabled')->default(false)->after('whatsapp_notifications_enabled');
            $table->string('daily_reminder_time', 5)->default('20:00')->after('daily_reminder_enabled');
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('type', 30)->default('bill_payment');
            $table->string('channel', 20)->default('email');
            $table->decimal('amount', 18, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->string('reminder_time', 5)->default('09:00');
            $table->string('frequency', 20)->default('once');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'is_active', 'due_date']);
        });

        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->nullable()->constrained('reminders')->nullOnDelete();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('recipient', 150);
            $table->string('title', 150);
            $table->text('content');
            $table->string('status', 20)->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('reminders');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'whatsapp_api_key',
                'whatsapp_gateway_url',
                'email_notifications_enabled',
                'whatsapp_notifications_enabled',
                'daily_reminder_enabled',
                'daily_reminder_time',
            ]);
        });
    }
};
