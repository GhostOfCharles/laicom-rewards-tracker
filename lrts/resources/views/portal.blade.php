<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS - Login Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/laicom.css') }}">
</head>
<body class="lrts-auth-page" style="--lrts-auth-bg-image: url('{{ asset('images/portal-bg.jpg') }}');">

    <div class="portal-card">
        <!-- Combined logo + LRTS image -->
        <img src="{{ asset('images/laicom-logo-header.png') }}" alt="LRTS" class="logo-header">

        <!-- Subtitle -->
        <div class="subtitle">LAICOM REWARDS TRACKER SYSTEM</div>

        <!-- Buttons -->
        <a href="{{ route('login.customer') }}" class="btn-portal">
            <i class="bi bi-people-fill"></i> CUSTOMER
        </a>
        <a href="{{ route('login.admin') }}" class="btn-portal">
            <i class="bi bi-gear-fill"></i> ADMIN
        </a>

        <!-- Footer -->
        <div class="card-footer-text">
            LAICOM SALES &amp; PROMOTIONS, INC.<br>
            <span class="small-line">REWARDS TODAY. STRONGER TOMORROW.</span>
        </div>
    </div>

</body>
</html>
