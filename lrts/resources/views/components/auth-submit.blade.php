@props(['label', 'loadingLabel' => 'Saving...'])

<button type="submit" class="auth-primary" data-auth-submit data-loading-label="{{ $loadingLabel }}">
    <span data-auth-submit-label aria-live="polite">{{ $label }}</span>
    <span class="auth-submit-spinner" aria-hidden="true" hidden></span>
</button>
