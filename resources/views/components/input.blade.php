@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'autofocus' => false,
    'autocomplete' => null,
    'hint' => null,
])
@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<div class="field-group">
    @if ($label)
        <label for="{{ $id }}" class="field-label">
            {{ $label }}
            @if ($required)
                <span class="text-rose-600 font-bold">*</span>
            @endif
        </label>
    @endif

    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        id="{{ $id }}" 
        value="{{ old($name, $value) }}" 
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'field-input' . ($hasError ? ' has-error' : '')]) }}
    >

    @error($name)
        <div class="field-error">
            <svg class="w-3.5 h-3.5 inline flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $message }}</span>
        </div>
    @enderror

    @if ($hint && !$hasError)
        <div class="field-hint">{{ $hint }}</div>
    @endif
</div>
