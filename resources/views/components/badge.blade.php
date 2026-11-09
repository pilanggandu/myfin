@props([
    'variant' => 'default', // personal, shared, active, inactive, default
])
@php
    $classes = match($variant) {
        'personal' => 'bg-cream text-charcoal',
        'shared' => 'bg-forest-light text-forest',
        'active' => 'bg-forest-light text-forest',
        'inactive' => 'bg-cream text-muted',
        default => 'bg-ivory text-charcoal border border-line',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider ' . $classes]) }}>
    {{ $slot }}
</span>
