@props([
    'variant' => 'default', // personal, shared, active, inactive, default
])
@php
    $classes = match($variant) {
        'personal' => 'bg-cream text-charcoal',
        'shared' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300',
        'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200',
        'inactive' => 'bg-cream text-muted',
        default => 'bg-ivory text-charcoal border border-line',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider ' . $classes]) }}>
    {{ $slot }}
</span>
