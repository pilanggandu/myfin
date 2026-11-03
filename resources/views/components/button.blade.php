@props([
    'variant' => 'primary', // primary, secondary, danger, danger-solid
    'type' => 'button',
])
@php
    $classes = match($variant) {
        'primary' => 'btn-arus-primary cursor-pointer',
        'secondary' => 'btn-arus-secondary cursor-pointer',
        'danger' => 'btn-arus-danger cursor-pointer',
        'danger-solid' => 'btn-arus-danger-solid cursor-pointer',
        default => 'btn-arus-primary cursor-pointer',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
