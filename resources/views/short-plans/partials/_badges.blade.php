@props([
    'plan',
    'now' => null,
])

@php
    $statusClasses = match ($plan->status) {
        'done' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-200 dark:border-emerald-800/60',
        'cancelled' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
        default => 'bg-forest-light text-forest border-forest/20 dark:bg-blue-950/60 dark:text-blue-200 dark:border-blue-800/60',
    };

    $priorityClasses = match ($plan->priority) {
        'high' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-200 dark:border-rose-800/60',
        'low' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
        default => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-200 dark:border-amber-800/60',
    };

    $dueStatus = $plan->dueStatus($now);
    $dueClasses = match ($dueStatus) {
        'overdue' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-200 dark:border-rose-800/60',
        'due_today' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-200 dark:border-amber-800/60',
        'soon' => 'bg-sky-50 text-sky-800 border-sky-200 dark:bg-sky-950/60 dark:text-sky-200 dark:border-sky-800/60',
        default => 'bg-cream text-charcoal border-line',
    };
@endphp

<div class="inline-flex items-center flex-wrap gap-1.5">
    <span class="px-2 py-0.5 rounded text-[10.5px] font-bold border {{ $statusClasses }}">
        {{ $plan->statusLabel() }}
    </span>

    @if ($plan->isPlanned())
        <span class="px-2 py-0.5 rounded text-[10.5px] font-bold border {{ $priorityClasses }}">
            Prioritas: {{ $plan->priorityLabel() }}
        </span>

        @if ($plan->target_date)
            <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold border {{ $dueClasses }}">
                {{ $plan->dueStatusLabel($now) }}
            </span>
        @else
            <span class="px-2 py-0.5 rounded text-[10.5px] font-medium border border-line bg-cream text-charcoal">
                Tanggal Fleksibel
            </span>
        @endif
    @endif
</div>
