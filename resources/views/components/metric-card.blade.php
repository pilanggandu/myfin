@props([
    'label',
    'value',
    'dark' => false,
    'sub' => null,
    'badge' => null,
    'toggle' => false,
    'notch' => null,
    'progress' => null,
])

@php
    $isDark = ! empty($dark);
    $darkTone = is_string($dark) ? $dark : 'green';
    $darkCardClass = match($darkTone) {
        'red' => 'dark dark-red',
        'blue' => 'dark dark-blue',
        default => 'dark dark-green',
    };
    $darkMutedText = match($darkTone) {
        'red' => 'text-rose-100',
        'blue' => 'text-blue-100',
        default => 'text-emerald-100',
    };
@endphp

<div {{ $attributes->merge(['class' => 'stat-folder-card ' . ($isDark ? $darkCardClass : '')]) }}>
    @if ($notch)
        <div class="stat-card-notch stat-notch-{{ $notch }}"></div>
    @endif
    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between gap-1">
                <span class="text-[10.5px] font-bold tracking-[1.2px] uppercase {{ $isDark ? $darkMutedText : 'text-muted' }}">
                    {{ $label }}
                </span>
                @if ($toggle)
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -mr-2 -mt-2 sm:mr-0 sm:mt-0 rounded-lg sm:rounded {{ $isDark ? 'border border-white/25 text-white hover:bg-white/15 bg-white/10' : 'border border-line text-muted hover:text-forest bg-panel' }} transition inline-flex items-center justify-center cursor-pointer text-[10px] shrink-0" 
                        title="Sembunyikan / Tampilkan Angka Metrik Ini"
                        aria-label="Sembunyikan atau tampilkan angka metrik"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                @endif
            </div>

            @if ($progress !== null)
                <div class="mt-2.5 flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold {{ $isDark ? $darkMutedText : 'text-muted' }}">Progress</span>
                    <span class="text-xs font-bold {{ $isDark ? 'text-white' : 'text-forest dark:text-blue-400' }} font-mono">{{ $progress }}%</span>
                </div>
                <div class="segmented-progress mt-1.5" aria-hidden="true">
                    @php
                        $totalBars = 20;
                        $activeBars = (int) round(($progress / 100) * $totalBars);
                    @endphp
                    @for ($i = 0; $i < $totalBars; $i++)
                        <span class="segmented-bar {{ $i < $activeBars ? 'active' : '' }}"></span>
                    @endfor
                </div>
            @endif

            <div class="text-xl sm:text-2xl lg:text-3xl font-bold tracking-tight mt-2.5 break-words {{ $isDark ? 'text-white' : 'text-charcoal' }}">
                @if (str_contains((string)$value, 'Rp'))
                    <span class="privacy-balance" data-raw-value="{{ $value }}">{{ $value }}</span>
                @else
                    {{ $value }}
                @endif
            </div>
        </div>

        <div class="pt-3 mt-3 border-t text-xs {{ $isDark ? 'border-white/10 ' . $darkMutedText : 'border-line text-muted' }} flex flex-wrap items-center justify-between gap-1.5">
            @if ($sub)
                @if (str_contains((string)$sub, 'Rp'))
                    <span class="privacy-balance" data-raw-value="{{ $sub }}">{{ $sub }}</span>
                @else
                    <span>{{ $sub }}</span>
                @endif
            @endif
            @if ($badge)
                <span class="font-bold text-[10.5px] uppercase {{ $isDark ? 'bg-white/20 text-white' : 'bg-cream text-charcoal' }} px-2 py-0.5 rounded">
                    {{ $badge }}
                </span>
            @endif
            @if (isset($footer))
                {{ $footer }}
            @endif
        </div>
    </div>
</div>
