@props([
    'label',
    'value',
    'dark' => false,
    'sub' => null,
    'badge' => null,
])

<div {{ $attributes->merge(['class' => 'metric-card ' . ($dark ? 'dark' : '') . ' flex flex-col justify-between min-h-[140px]']) }}>
    <div>
        <span class="text-[11px] font-bold tracking-[1.2px] uppercase {{ $dark ? 'text-emerald-200' : 'text-muted' }}">
            {{ $label }}
        </span>
        <div class="font-serif text-3xl font-normal tracking-tight mt-3 {{ $dark ? 'text-white' : 'text-forest' }}">
            @if (str_contains((string)$value, 'Rp'))
                <span class="privacy-balance" data-raw-value="{{ $value }}">{{ $value }}</span>
            @else
                {{ $value }}
            @endif
        </div>
    </div>
    <div class="pt-3 border-t text-xs {{ $dark ? 'border-white/10 text-emerald-100/90' : 'border-line text-muted' }} flex items-center justify-between">
        @if ($sub)
            @if (str_contains((string)$sub, 'Rp'))
                <span class="privacy-balance" data-raw-value="{{ $sub }}">{{ $sub }}</span>
            @else
                <span>{{ $sub }}</span>
            @endif
        @endif
        @if ($badge)
            <span class="font-bold text-[11px] uppercase {{ $dark ? 'bg-white/20 text-white' : 'bg-cream text-charcoal' }} px-2 py-0.5 rounded">
                {{ $badge }}
            </span>
        @endif
        @if (isset($footer))
            {{ $footer }}
        @endif
    </div>
</div>
