<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendRemindersCommand extends Command
{
    protected $signature = 'arus:send-reminders';

    protected $description = 'Kirimkan seluruh pengingat keuangan yang jatuh tempo via Email dan WhatsApp (100% Gratis)';

    public function handle(ReminderService $reminderService): int
    {
        $this->info('Memeriksa pengingat jatuh tempo...');
        $count = $reminderService->checkAndDispatchDueReminders();
        $this->info("Selesai. Sebanyak {$count} pengingat berhasil diproses.");

        return Command::SUCCESS;
    }
}
