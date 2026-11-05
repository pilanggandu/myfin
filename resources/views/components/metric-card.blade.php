@props([
    'label',
    'value',
    'dark' => false,
    'sub' => null,
    'badge' => null,
    'toggle' => false,
])

<div {{ $attributes->merge(['class' => 'metric-card ' . ($dark ? 'dark' : '') . ' flex flex-col justify-between min-h-[140px]']) }}>
    <div>
        <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold tracking-[1.2px] uppercase {{ $dark ? 'text-emerald-200' : 'text-muted' }}">
                {{ $label }}
            </span>
            @if ($toggle)
                <button 
                    type="button" 
                    onclick="toggleCardPrivacy(this)" 
                    class="privacy-card-toggle w-5 h-5 rounded {{ $dark ? 'border border-white/20 text-white/70 hover:text-white bg-white/10' : 'border border-line text-muted hover:text-forest bg-panel' }} transition inline-flex items-center justify-center cursor-pointer text-[10px] flex-shrink-0" 
                    title="Sembunyikan / Tampilkan Angka Metrik Ini"
                >
                    <span class="privacy-toggle-icon"></span>
                </button>
            @endif
        </div>
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
