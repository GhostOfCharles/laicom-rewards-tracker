@props(['heading', 'description'])

<header class="auth-header">
    <img src="{{ asset('images/rts-logo.png') }}" alt="Rewards Tracker System logo" class="auth-logo">
    <div class="auth-intro">
        <h1 class="auth-title" id="auth-title">{{ $heading }}</h1>
        <p class="auth-description">{{ $description }}</p>
    </div>
</header>
