<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Welcome') | Rewards Tracker System</title>
    @php
        $authBackgroundUrl = asset('images/portal-bg.jpg') . '?v=' . filemtime(public_path('images/portal-bg.jpg'));
    @endphp
    <style>html { background: #292627; }</style>
    <link rel="preload" as="image" href="{{ $authBackgroundUrl }}" fetchpriority="high">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
</head>
<body class="lrts-auth-page @yield('auth-page-class')" style="--auth-background-image: url('{{ $authBackgroundUrl }}');">
    <main class="auth-card @yield('auth-card-class')" aria-labelledby="auth-title">
        @hasSection('auth-back')
            <a href="@yield('auth-back')" class="auth-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
        @endif
        <x-auth-header :heading="$__env->yieldContent('auth-heading')" :description="$__env->yieldContent('auth-description')" />

        @if ($errors->any())
            <div class="auth-alert" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')

        <x-auth-footer />
    </main>
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
    @stack('scripts')
</body>
</html>
