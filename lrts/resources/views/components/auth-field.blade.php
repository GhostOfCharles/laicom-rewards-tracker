@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'toggleId' => null,
    'describedBy' => null,
])

@php
    $fieldId = $id ?: 'auth-' . str_replace('_', '-', $name);
    $invalid = $errors->has($name);
    $descriptionIds = trim(($describedBy ?? '') . ($invalid ? ' ' . $fieldId . '-error' : ''));
@endphp

<div class="auth-field-group">
    <label for="{{ $fieldId }}" class="auth-sr-only">{{ $label }}</label>
    <div @class(['auth-field', 'has-error' => $invalid])>
        @if ($icon)
            <i class="bi bi-{{ $icon }} auth-field-icon" aria-hidden="true"></i>
        @endif
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            placeholder="{{ $placeholder ?? $label }}"
            @if ($type !== 'password') value="{{ $value }}" @endif
            {{ $attributes->class(['auth-input', 'auth-input--password' => $type === 'password'])->merge([
                'aria-invalid' => $invalid ? 'true' : 'false',
                'aria-describedby' => $descriptionIds ?: null,
            ]) }}
        >
        @if ($type === 'password')
            <button
                type="button"
                class="auth-password-toggle"
                @if ($toggleId) id="{{ $toggleId }}" @endif
                data-auth-password-toggle
                data-password-name="{{ strtolower($label) }}"
                aria-controls="{{ $fieldId }}"
                aria-label="Show {{ strtolower($label) }}"
                aria-pressed="false"
            ><span data-password-label>Show</span></button>
        @endif
    </div>
    @error($name)
        <p class="auth-field-error" id="{{ $fieldId }}-error">{{ $message }}</p>
    @enderror
</div>
