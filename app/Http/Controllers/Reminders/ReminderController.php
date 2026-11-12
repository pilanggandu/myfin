<?php

namespace App\Http\Controllers\Reminders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reminder\StoreReminderRequest;
use App\Http\Requests\Reminder\UpdateNotificationSettingsRequest;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Services\ActivityLogService;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function __construct(
        protected ReminderService $reminderService,
        protected ActivityLogService $activityLog
    ) {}

    public function index(Request $request): View
    {
        $space = app('currentSpace');
        $user = $request->user();

        $query = Reminder::where('space_id', $space->id)
            ->where('user_id', $user->id);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($channel = $request->input('channel')) {
            $query->where('channel', $channel);
        }

        $reminders = $query->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $allReminders = Reminder::where('space_id', $space->id)->where('user_id', $user->id)->get();
        $totalActive = $allReminders->where('is_active', true)->count();
        $emailCount = $allReminders->whereIn('channel', ['email', 'both'])->count();
        $waCount = $allReminders->whereIn('channel', ['whatsapp', 'both'])->count();
        $dueThisMonth = $allReminders->filter(fn ($r) => $r->due_date && $r->due_date->isCurrentMonth())->count();

        $recentLogs = ReminderLog::where('space_id', $space->id)
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return view('reminders.index', compact(
            'space',
            'user',
            'reminders',
            'totalActive',
            'emailCount',
            'waCount',
            'dueThisMonth',
            'recentLogs'
        ));
    }

    public function store(StoreReminderRequest $request): RedirectResponse
    {
        $space = app('currentSpace');
        $user = $request->user();

        $data = $request->validated();
        $data['space_id'] = $space->id;
        $data['user_id'] = $user->id;
        $data['is_active'] = $request->boolean('is_active', true);

        $reminder = Reminder::create($data);

        $this->activityLog->log(
            module: 'reminder',
            action: 'created',
            description: "Membuat pengingat \"{$reminder->title}\"",
            space: $space,
            actor: $user,
            subject: $reminder,
            subjectLabel: $reminder->title,
            properties: [
                'channel' => $reminder->channel ?? null,
                'due_date' => $reminder->due_date?->toDateString(),
                'is_active' => (bool) $reminder->is_active,
            ],
            request: $request,
        );

        return redirect()->route('reminders.index')
            ->with('success', "Pengingat \"{$reminder->title}\" berhasil dibuat.");
    }

    public function update(StoreReminderRequest $request, Reminder $reminder): RedirectResponse
    {
        $before = [
            'title' => $reminder->title,
            'is_active' => (bool) $reminder->is_active,
            'due_date' => $reminder->due_date?->toDateString(),
        ];
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $reminder->update($data);

        $diff = $this->activityLog->buildDiff($before, [
            'title' => $reminder->title,
            'is_active' => (bool) $reminder->is_active,
            'due_date' => $reminder->due_date?->toDateString(),
        ], [
            'title' => 'Judul Pengingat',
            'is_active' => 'Aktif',
            'due_date' => 'Tanggal Jatuh Tempo',
        ]);

        $this->activityLog->log(
            module: 'reminder',
            action: 'updated',
            description: "Memperbarui pengingat \"{$reminder->title}\"",
            space: $reminder->space,
            actor: $request->user(),
            subject: $reminder,
            subjectLabel: $reminder->title,
            properties: $diff,
            request: $request,
        );

        return redirect()->route('reminders.index')
            ->with('success', "Pengingat \"{$reminder->title}\" berhasil diperbarui.");
    }

    public function destroy(Reminder $reminder): RedirectResponse
    {
        $title = $reminder->title;
        $space = $reminder->space;
        $reminder->delete();

        $this->activityLog->log(
            module: 'reminder',
            action: 'deleted',
            description: "Menghapus pengingat \"{$title}\"",
            space: $space,
            subjectLabel: $title,
        );

        return redirect()->route('reminders.index')
            ->with('success', "Pengingat \"{$title}\" berhasil dihapus.");
    }

    public function toggleActive(Reminder $reminder): RedirectResponse
    {
        $reminder->is_active = ! $reminder->is_active;
        $reminder->save();

        $statusText = $reminder->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $this->activityLog->log(
            module: 'reminder',
            action: 'toggled',
            description: "Mengubah status pengingat \"{$reminder->title}\" menjadi ".($reminder->is_active ? 'aktif' : 'nonaktif'),
            space: $reminder->space,
            subject: $reminder,
            subjectLabel: $reminder->title,
            properties: ['is_active' => (bool) $reminder->is_active],
        );

        return redirect()->route('reminders.index')
            ->with('success', "Pengingat \"{$reminder->title}\" berhasil {$statusText}.");
    }

    public function sendNow(Reminder $reminder): RedirectResponse
    {
        $results = $this->reminderService->sendReminder($reminder);

        $this->activityLog->log(
            module: 'reminder',
            action: 'sent',
            description: "Mengirim pengingat manual \"{$reminder->title}\"",
            space: $reminder->space,
            subject: $reminder,
            subjectLabel: $reminder->title,
        );

        $messages = [];
        if (! empty($results['email']['message'])) {
            $messages[] = $results['email']['message'];
        }
        if (! empty($results['whatsapp']['message'])) {
            $messages[] = $results['whatsapp']['message'];
        }

        $sessionData = ['success' => implode(' | ', $messages)];
        if (! empty($results['whatsapp']['direct_link'])) {
            $sessionData['wa_direct_link'] = $results['whatsapp']['direct_link'];
        }

        return redirect()->route('reminders.index')
            ->with($sessionData);
    }

    public function sendTest(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'channel' => ['required', 'string', 'in:email,whatsapp,both'],
            'target_phone' => ['nullable', 'string', 'max:25'],
        ]);

        $user = $request->user();
        $space = app('currentSpace');

        $results = $this->reminderService->sendTestReminder(
            $user,
            $space,
            $request->input('channel'),
            $request->input('target_phone')
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        }

        $msgList = [];
        if (! empty($results['email']['message'])) {
            $msgList[] = $results['email']['message'];
        }
        if (! empty($results['whatsapp']['message'])) {
            $msgList[] = $results['whatsapp']['message'];
        }

        $sessionData = ['success' => implode(' | ', $msgList)];
        if (! empty($results['whatsapp']['direct_link'])) {
            $sessionData['wa_direct_link'] = $results['whatsapp']['direct_link'];
        }

        return redirect()->route('reminders.index')
            ->with($sessionData);
    }

    public function updateSettings(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'phone' => $validated['phone'] ?? null,
            'whatsapp_api_key' => $validated['whatsapp_api_key'] ?? null,
            'whatsapp_gateway_url' => $validated['whatsapp_gateway_url'] ?? null,
            'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
            'whatsapp_notifications_enabled' => $request->boolean('whatsapp_notifications_enabled'),
            'daily_reminder_enabled' => $request->boolean('daily_reminder_enabled'),
            'daily_reminder_time' => $validated['daily_reminder_time'] ?? '20:00',
        ]);

        $this->activityLog->log(
            module: 'reminder',
            action: 'settings_updated',
            description: 'Memperbarui konfigurasi saluran pengingat Email & WhatsApp',
            space: app()->bound('currentSpace') ? app('currentSpace') : null,
            actor: $user,
            properties: [
                'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
                'whatsapp_notifications_enabled' => $request->boolean('whatsapp_notifications_enabled'),
                'daily_reminder_enabled' => $request->boolean('daily_reminder_enabled'),
            ],
            request: $request,
        );

        return redirect()->route('reminders.index')
            ->with('success', 'Konfigurasi saluran pengingat Email & WhatsApp berhasil disimpan.');
    }
}
