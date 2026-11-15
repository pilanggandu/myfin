@props([
    'fallback' => url('/'),
    'returnTo' => null,
    'label' => 'Kembali',
    'variant' => 'link', // 'link', 'button', 'icon'
])

@php
    $resolvedReturnTo = $returnTo ?? request()->query('return_to');

    // Validasi return_to: hanya perbolehkan path internal relatif atau URL dengan domain yang sama
    $isValidReturnTo = false;
    if (! empty($resolvedReturnTo) && is_string($resolvedReturnTo)) {
        if (str_starts_with($resolvedReturnTo, '/') && ! str_starts_with($resolvedReturnTo, '//')) {
            $isValidReturnTo = true;
        } elseif (str_starts_with($resolvedReturnTo, url('/'))) {
            $isValidReturnTo = true;
        }
    }

    $targetUrl = $isValidReturnTo ? $resolvedReturnTo : $fallback;

    $baseClasses = match($variant) {
        'button' => 'btn-arus-secondary min-h-[46px] px-5 text-sm font-bold inline-flex items-center justify-center cursor-pointer',
        'icon' => 'w-11 h-11 sm:w-9 sm:h-9 shrink-0 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 flex items-center justify-center text-muted hover:text-charcoal transition cursor-pointer',
        default => 'text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5 cursor-pointer',
    };
@endphp

<a 
    href="{{ $targetUrl }}" 
    onclick="if (!{{ $isValidReturnTo ? 'true' : 'false' }} && window.history.length > 1 && document.referrer) { try { const ref = new URL(document.referrer); if (ref.origin === window.location.origin && ref.pathname !== window.location.pathname) { event.preventDefault(); window.history.back(); } } catch(e){} }"
    @if($variant === 'icon')
        title="{{ $label }}"
        aria-label="{{ $label }}"
    @endif
    {{ $attributes->merge(['class' => $baseClasses]) }}
>
    @if($variant === 'icon')
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
    @elseif($variant === 'button')
        {{ $slot->isNotEmpty() ? $slot : $label }}
    @else
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        <span>{{ $slot->isNotEmpty() ? $slot : $label }}</span>
    @endif
</a>
