<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS - Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/laicom.css') }}">
</head>
<body class="lrts-auth-page" style="--lrts-auth-bg-image: url('{{ asset('images/portal-bg.jpg') }}');">
    <div class="login-card">
        <img src="{{ asset('images/laicom-logo-header.png') }}" alt="LRTS" class="logo-header">
        <div class="subtitle">LAICOM REWARDS TRACKER SYSTEM</div>

        <h5 class="welcome-title">WELCOME, ADMIN</h5>
        <div class="title-divider"></div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.admin.submit') }}" method="POST">
            @csrf

            <div class="input-icon">
                <i class="bi bi-envelope-fill"></i>
                <input type="email" name="email" placeholder="EMAIL" required autofocus value="{{ old('email') }}">
            </div>

            <div class="input-icon">
                <i class="bi bi-lock-fill"></i>
                <input type="password" name="password" placeholder="PASSWORD" required>
            </div>

            <button type="submit" class="btn-login">LOGIN</button>
        </form>

        <div class="card-footer-text">
            LAICOM SALES &amp; PROMOTIONS, INC.<br>
            <span class="small-line">REWARDS TODAY. STRONGER TOMORROW.</span>
        </div>
    </div>
</body>
</html>
