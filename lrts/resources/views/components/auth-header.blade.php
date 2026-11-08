@props(['heading', 'description'])

<header class="auth-header">
    <img src="{{ asset('images/rts-logo-header.webp') }}" alt="Rewards Tracker System logo" class="auth-logo" fetchpriority="high">
    <div class="auth-intro">
        @if ($heading)
            <h1 class="auth-title" id="auth-title">{{ $heading }}</h1>
        @endif
        <p class="auth-description" id="auth-description">{{ $description }}</p>
    </div>
</header>
