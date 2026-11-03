@props([
    'name',
    'label',
    'checked' => false,
    'value' => '1',
])
@php
    $id = $attributes->get('id', $name);
@endphp

<div class="flex items-center gap-2.5 my-3">
    <input 
        type="checkbox" 
        name="{{ $name }}" 
        id="{{ $id }}" 
        value="{{ $value }}"
        {{ old($name, $checked) ? 'checked' : '' }}
        {{ $attributes->merge(['class' => 'field-checkbox']) }}
    >
    <label for="{{ $id }}" class="text-xs text-muted cursor-pointer select-none">
        {{ $label }}
    </label>
</div>
