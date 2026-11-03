<?php

namespace Tests\Feature;

use App\Mail\ReminderMail;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Space $space;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Budi Finansial',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'email_notifications_enabled' => true,
            'whatsapp_notifications_enabled' => true,
        ]);

        $this->space = Space::create([
            'name' => 'Ruang Personal Budi',
            'type' => 'personal',
            'owner_id' => $this->user->id,
            'timezone' => 'Asia/Jakarta',
        ]);
        $this->space->members()->attach($this->user->id, ['role' => 'owner', 'status' => 'active']);
    }

    public function test_user_can_view_reminders_index_page(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->get(route('reminders.index'));

        $response->assertOk();
        $response->assertSee('Pengingat Keuangan');
        $response->assertSee('Status Layanan: 100% Gratis');
    }

    public function test_user_can_create_new_reminder(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('reminders.store'), [
                'title' => 'Tagihan Listrik PLN',
                'type' => 'bill_payment',
                'channel' => 'both',
                'amount' => 350000,
                'due_date' => '2026-11-20',
                'reminder_time' => '08:00',
                'frequency' => 'monthly',
                'notes' => 'Bayar lewat m-BCA',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('reminders.index'));
        $this->assertDatabaseHas('reminders', [
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
            'title' => 'Tagihan Listrik PLN',
            'channel' => 'both',
            'amount' => 350000,
        ]);
    }

    public function test_user_can_update_reminder(): void
    {
        $reminder = Reminder::create([
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
            'title' => 'Tagihan Wifi',
            'type' => 'bill_payment',
            'channel' => 'email',
            'amount' => 250000,
            'due_date' => '2026-11-15',
            'reminder_time' => '09:00',
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->put(route('reminders.update', $reminder), [
                'title' => 'Tagihan Wifi MyRepublic (Updated)',
                'type' => 'bill_payment',
                'channel' => 'whatsapp',
                'amount' => 280000,
                'due_date' => '2026-11-16',
                'reminder_time' => '10:00',
                'frequency' => 'monthly',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('reminders.index'));
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'title' => 'Tagihan Wifi MyRepublic (Updated)',
            'channel' => 'whatsapp',
            'amount' => 280000,
        ]);
    }

    public function test_user_can_toggle_reminder_active_status(): void
    {
        $reminder = Reminder::create([
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
            'title' => 'Catat Keuangan Harian',
            'type' => 'daily_bookkeeping',
            'channel' => 'whatsapp',
            'frequency' => 'daily',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('reminders.toggleActive', $reminder));

        $response->assertRedirect(route('reminders.index'));
        $this->assertFalse($reminder->fresh()->is_active);
    }

    public function test_user_can_delete_reminder(): void
    {
        $reminder = Reminder::create([
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
            'title' => 'Pengingat Sementara',
            'type' => 'custom',
            'channel' => 'email',
            'frequency' => 'once',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->delete(route('reminders.destroy', $reminder));

        $response->assertRedirect(route('reminders.index'));
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }

    public function test_user_can_update_notification_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('reminders.updateSettings'), [
                'phone' => '089876543210',
                'whatsapp_api_key' => 'free-key-1234',
                'email_notifications_enabled' => 1,
                'whatsapp_notifications_enabled' => 1,
                'daily_reminder_enabled' => 1,
                'daily_reminder_time' => '21:00',
            ]);

        $response->assertRedirect(route('reminders.index'));
        $fresh = $this->user->fresh();
        $this->assertEquals('089876543210', $fresh->phone);
        $this->assertEquals('free-key-1234', $fresh->whatsapp_api_key);
        $this->assertTrue($fresh->daily_reminder_enabled);
        $this->assertEquals('21:00', $fresh->daily_reminder_time);
    }

    public function test_user_can_send_test_email_reminder(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('reminders.sendTest'), [
                'channel' => 'email',
            ]);

        $response->assertRedirect(route('reminders.index'));
        $response->assertSessionHas('success');

        Mail::assertSent(ReminderMail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });

        $this->assertDatabaseHas('reminder_logs', [
            'user_id' => $this->user->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_user_can_send_test_whatsapp_reminder_direct_link(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('reminders.sendTest'), [
                'channel' => 'whatsapp',
                'target_phone' => '081234567890',
            ]);

        $response->assertRedirect(route('reminders.index'));
        $response->assertSessionHas('wa_direct_link');

        $this->assertDatabaseHas('reminder_logs', [
            'user_id' => $this->user->id,
            'channel' => 'whatsapp',
            'recipient' => '6281234567890',
        ]);
    }

    public function test_user_can_send_reminder_now(): void
    {
        Mail::fake();

        $reminder = Reminder::create([
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
            'title' => 'Tagihan Air PDAM',
            'type' => 'bill_payment',
            'channel' => 'both',
            'amount' => 125000,
            'due_date' => Carbon::today(),
            'reminder_time' => '08:00',
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_space_id' => $this->space->id])
            ->post(route('reminders.sendNow', $reminder));

        $response->assertRedirect(route('reminders.index'));
        $response->assertSessionHas('success');

        Mail::assertSent(ReminderMail::class);
        $this->assertNotNull($reminder->fresh()->last_sent_at);
    }

    public function test_artisan_send_reminders_command(): void
    {
        Mail::fake();

        Reminder::create([
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
            'title' => 'Pengingat Jatuh Tempo Hari Ini',
            'type' => 'bill_payment',
            'channel' => 'email',
            'amount' => 500000,
            'due_date' => Carbon::today(),
            'frequency' => 'once',
            'is_active' => true,
        ]);

        $this->artisan('arus:send-reminders')
            ->assertExitCode(0);

        Mail::assertSent(ReminderMail::class);
    }
}
