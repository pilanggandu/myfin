<?php

namespace App\Services;

use App\Mail\ReminderMail;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ReminderService
{
    public function sendReminder(Reminder $reminder, ?User $user = null): array
    {
        $user = $user ?? $reminder->user;
        $space = $reminder->space;
        $title = $reminder->title;
        $channel = $reminder->channel;

        $messageText = $this->buildReminderMessage($reminder);
        $details = $this->buildReminderDetails($reminder);

        $results = [
            'email' => null,
            'whatsapp' => null,
        ];

        if ($channel === 'email' || $channel === 'both') {
            $results['email'] = $this->dispatchEmail($user, $space, $reminder, $title, $messageText, $details);
        }

        if ($channel === 'whatsapp' || $channel === 'both') {
            $results['whatsapp'] = $this->dispatchWhatsApp($user, $space, $reminder, $title, $messageText);
        }

        $reminder->update(['last_sent_at' => Carbon::now()]);

        return $results;
    }

    public function sendTestReminder(User $user, Space $space, string $channel = 'both', ?string $targetPhone = null): array
    {
        $title = 'Uji Coba Pengingat ARUS';
        $messageText = 'Halo '.$user->name.'! Ini adalah pesan uji coba sistem pengingat ARUS. Konfigurasi notifikasi Anda telah aktif dan siap digunakan.';
        $details = [
            'Tipe Pesan' => 'Uji Coba Sistem (Test)',
            'Ruang Keuangan' => $space->name,
            'Waktu Eksekusi' => Carbon::now()->format('d M Y H:i:s').' WIB',
            'Status Biaya' => '100% Gratis Tanpa Langganan',
        ];

        $results = [
            'email' => null,
            'whatsapp' => null,
        ];

        if ($channel === 'email' || $channel === 'both') {
            $results['email'] = $this->dispatchEmail($user, $space, null, $title, $messageText, $details);
        }

        if ($channel === 'whatsapp' || $channel === 'both') {
            $phoneToUse = $targetPhone ?? $user->phone;
            $results['whatsapp'] = $this->dispatchWhatsApp($user, $space, null, $title, $messageText, $phoneToUse);
        }

        return $results;
    }

    public function dispatchEmail(User $user, Space $space, ?Reminder $reminder, string $title, string $messageText, array $details = []): array
    {
        $recipient = $user->email;

        try {
            Mail::to($recipient)->send(new ReminderMail(
                userName: $user->name,
                subjectTitle: $title,
                messageText: $messageText,
                details: $details,
                actionUrl: route('dashboard')
            ));

            $log = ReminderLog::create([
                'reminder_id' => $reminder?->id,
                'space_id' => $space->id,
                'user_id' => $user->id,
                'channel' => 'email',
                'recipient' => $recipient,
                'title' => $title,
                'content' => $messageText,
                'status' => 'sent',
            ]);

            return [
                'success' => true,
                'channel' => 'email',
                'recipient' => $recipient,
                'message' => 'Email pengingat berhasil dikirim ke '.$recipient,
                'log_id' => $log->id,
            ];
        } catch (\Throwable $e) {
            $log = ReminderLog::create([
                'reminder_id' => $reminder?->id,
                'space_id' => $space->id,
                'user_id' => $user->id,
                'channel' => 'email',
                'recipient' => $recipient,
                'title' => $title,
                'content' => $messageText,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'channel' => 'email',
                'recipient' => $recipient,
                'message' => 'Gagal mengirim email: '.$e->getMessage(),
                'log_id' => $log->id,
            ];
        }
    }

    public function dispatchWhatsApp(User $user, Space $space, ?Reminder $reminder, string $title, string $messageText, ?string $overridePhone = null): array
    {
        $rawPhone = $overridePhone ?? $user->phone;
        if (! $rawPhone) {
            return [
                'success' => false,
                'channel' => 'whatsapp',
                'message' => 'Nomor WhatsApp belum dikonfigurasi.',
                'direct_link' => null,
            ];
        }

        $phone = $this->normalizePhoneNumber($rawPhone);
        $fullText = "*[ARUS PENGINGAT]*\n\n"
            ."*{$title}*\n"
            ."Halo *{$user->name}*,\n\n"
            ."{$messageText}\n\n"
            ."Ruang: {$space->name}\n"
            .'Waktu: '.Carbon::now()->format('d/m/Y H:i')." WIB\n\n"
            .'Buka aplikasi: '.url('/dashboard');

        $directLink = $this->generateWhatsAppDirectLink($phone, $fullText);
        $status = 'simulated';
        $error = null;

        $gatewayUrl = $user->whatsapp_gateway_url;
        $apiKey = $user->whatsapp_api_key;

        if ($gatewayUrl) {
            try {
                $resp = Http::timeout(6)->post($gatewayUrl, [
                    'phone' => $phone,
                    'message' => $fullText,
                    'api_key' => $apiKey,
                ]);
                $status = $resp->successful() ? 'sent' : 'failed';
                if (! $resp->successful()) {
                    $error = 'Gateway status: '.$resp->status();
                }
            } catch (\Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
            }
        } elseif ($apiKey) {
            try {
                $callMeBotUrl = 'https://api.callmebot.com/whatsapp.php';
                $resp = Http::timeout(6)->get($callMeBotUrl, [
                    'phone' => $phone,
                    'text' => $fullText,
                    'apikey' => $apiKey,
                ]);
                $status = $resp->successful() ? 'sent' : 'failed';
                if (! $resp->successful()) {
                    $error = 'CallMeBot status: '.$resp->status();
                }
            } catch (\Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
            }
        } else {
            $status = 'direct_ready';
        }

        $log = ReminderLog::create([
            'reminder_id' => $reminder?->id,
            'space_id' => $space->id,
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'recipient' => $phone,
            'title' => $title,
            'content' => $fullText,
            'status' => $status,
            'error_message' => $error,
        ]);

        return [
            'success' => $status !== 'failed',
            'channel' => 'whatsapp',
            'recipient' => $phone,
            'status' => $status,
            'direct_link' => $directLink,
            'message' => $status === 'sent'
                ? 'Pesan WhatsApp berhasil terkirim secara otomatis.'
                : 'Pesan WhatsApp siap dikirimkan melalui direct link (100% Gratis).',
            'log_id' => $log->id,
        ];
    }

    public function generateWhatsAppDirectLink(string $phone, string $message): string
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        $encodedText = rawurlencode($message);

        return "https://api.whatsapp.com/send?phone={$normalizedPhone}&text={$encodedText}";
    }

    public function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '08')) {
            return '628'.substr($digits, 2);
        }

        if (str_starts_with($digits, '8')) {
            return '628'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return $digits;
    }

    public function buildReminderMessage(Reminder $reminder): string
    {
        if ($reminder->notes) {
            return $reminder->notes;
        }

        return match ($reminder->type) {
            'bill_payment' => 'Pengingat jatuh tempo pembayaran tagihan "'.$reminder->title.'" sebesar Rp '.number_format($reminder->amount ?? 0, 0, ',', '.').' pada tanggal '.($reminder->due_date?->format('d M Y') ?? 'segera').'. Pastikan saldo rekening mencukupi.',
            'daily_bookkeeping' => 'Jangan lupa untuk mencatat mutasi pengeluaran dan pemasukan Anda hari ini di ARUS agar kesehatan arus kas tetap terkontrol dengan baik.',
            'budget_alert' => 'Perhatian: Pos anggaran Anda mendekati atau telah melampaui limit yang ditetapkan. Silakan evaluasi pengeluaran kategori terkait.',
            default => 'Ini adalah pengingat terjadwal untuk: '.$reminder->title.'.',
        };
    }

    public function buildReminderDetails(Reminder $reminder): array
    {
        $details = [
            'Tipe Pengingat' => $reminder->typeLabel(),
            'Saluran' => $reminder->channelLabel(),
            'Frekuensi' => $reminder->frequencyLabel(),
            'Ruang Keuangan' => $reminder->space?->name ?? 'Ruang Aktif',
        ];

        if ($reminder->amount && $reminder->amount > 0) {
            $details['Nominal'] = 'Rp '.number_format($reminder->amount, 0, ',', '.');
        }

        if ($reminder->due_date) {
            $details['Jatuh Tempo'] = $reminder->due_date->format('d M Y');
        }

        if ($reminder->reminder_time) {
            $details['Waktu Pengingat'] = $reminder->reminder_time.' WIB';
        }

        return $details;
    }

    public function checkAndDispatchDueReminders(): int
    {
        $today = Carbon::today()->format('Y-m-d');
        $reminders = Reminder::where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('due_date')
                    ->orWhereDate('due_date', '<=', $today);
            })
            ->with(['space', 'user'])
            ->get();

        $dispatched = 0;
        foreach ($reminders as $reminder) {
            if ($reminder->user && $reminder->space) {
                $this->sendReminder($reminder);
                $dispatched++;
            }
        }

        return $dispatched;
    }
}
