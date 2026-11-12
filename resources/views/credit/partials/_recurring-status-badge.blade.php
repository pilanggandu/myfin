@php
    $state = $schedule->isPaused() ? 'paused' : $schedule->dueStatus($tz);
    [$badgeClass, $badgeLabel] = match ($state) {
        'paused' => ['bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30', 'Dijeda'],
        'overdue' => ['bg-rose-500/15 text-rose-800 dark:text-rose-300 border-rose-500/30', 'Terlambat'],
        'due' => ['bg-amber-500/15 text-amber-800 dark:text-amber-300 border-amber-500/30', 'Hari Ini'],
        default => ['bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border-emerald-500/25', 'Terjadwal'],
    };
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
    {{ $badgeLabel }}
</span>
