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

<div {{ $attributes->merge(['class' => 'stat-folder-card ' . ($dark ? 'dark' : '')]) }}>
    @if ($notch)
        <div class="stat-card-notch stat-notch-{{ $notch }}"></div>
    @endif
    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between gap-1">
                <span class="text-[10.5px] font-bold tracking-[1.2px] uppercase {{ $dark ? 'text-blue-100' : 'text-muted' }}">
                    {{ $label }}
                </span>
                @if ($toggle)
                    <button 
                        type="button" 
                        onclick="toggleCardPrivacy(this)" 
                        class="privacy-card-toggle w-11 h-11 sm:w-6 sm:h-6 -mr-2 -mt-2 sm:mr-0 sm:mt-0 rounded-lg sm:rounded {{ $dark ? 'border border-white/20 text-white/80 hover:text-white bg-white/10' : 'border border-line text-muted hover:text-forest bg-panel' }} transition inline-flex items-center justify-center cursor-pointer text-[10px] shrink-0" 
                        title="Sembunyikan / Tampilkan Angka Metrik Ini"
                        aria-label="Sembunyikan atau tampilkan angka metrik"
                    >
                        <span class="privacy-toggle-icon"></span>
                    </button>
                @endif
            </div>

            @if ($progress !== null)
                <div class="mt-2.5 flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold text-muted">Progress</span>
                    <span class="text-xs font-bold text-forest dark:text-blue-400 font-mono">{{ $progress }}%</span>
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

            <div class="text-xl sm:text-2xl lg:text-3xl font-bold tracking-tight mt-2.5 break-words {{ $dark ? 'text-white' : 'text-charcoal' }}">
                @if (str_contains((string)$value, 'Rp'))
                    <span class="privacy-balance" data-raw-value="{{ $value }}">{{ $value }}</span>
                @else
                    {{ $value }}
                @endif
            </div>
        </div>

        <div class="pt-3 mt-3 border-t text-xs {{ $dark ? 'border-white/10 text-blue-100' : 'border-line text-muted' }} flex flex-wrap items-center justify-between gap-1.5">
            @if ($sub)
                @if (str_contains((string)$sub, 'Rp'))
                    <span class="privacy-balance" data-raw-value="{{ $sub }}">{{ $sub }}</span>
                @else
                    <span>{{ $sub }}</span>
                @endif
            @endif
            @if ($badge)
                <span class="font-bold text-[10.5px] uppercase {{ $dark ? 'bg-white/20 text-white' : 'bg-cream text-charcoal' }} px-2 py-0.5 rounded">
                    {{ $badge }}
                </span>
            @endif
            @if (isset($footer))
                {{ $footer }}
            @endif
        </div>
    </div>
</div>
