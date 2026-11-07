<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Welcome') | Rewards Tracker System</title>
    @php
        $authBackgroundUrl = asset('images/portal-bg.jpg') . '?v=' . filemtime(public_path('images/portal-bg.jpg'));
        $authBackgroundWebpUrl = asset('images/portal-bg.webp') . '?v=' . filemtime(public_path('images/portal-bg.webp'));
    @endphp
    <style>html { background: #292627; }</style>
    <link rel="preload" as="image" type="image/webp" href="{{ $authBackgroundWebpUrl }}" fetchpriority="high">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" type="image/x-icon">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
</head>
<body class="lrts-auth-page @yield('auth-page-class')" style="--auth-background-image: image-set(url('{{ $authBackgroundWebpUrl }}') type('image/webp'), url('{{ $authBackgroundUrl }}') type('image/jpeg')); ">
    <main class="auth-card @yield('auth-card-class')" aria-labelledby="{{ $__env->hasSection('auth-heading') ? 'auth-title' : 'auth-description' }}">
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

        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @yield('content')

        <x-auth-footer />
    </main>
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
    @stack('scripts')
</body>
</html>
