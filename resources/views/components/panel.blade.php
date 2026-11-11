@props([
    'title' => null,
    'subtitle' => null,
    'eyebrow' => null,
])

<div {{ $attributes->merge(['class' => 'panel-card']) }}>
    @if ($title || $eyebrow || isset($header))
        <div class="pb-3 border-b border-line mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0 flex-1">
                @if ($eyebrow)
                    <span class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase block mb-0.5">{{ $eyebrow }}</span>
                @endif
                @if ($title)
                    <h2 class="text-base font-bold text-forest m-0">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-muted mt-0.5 m-0">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($header))
                <div class="shrink-0">{{ $header }}</div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
