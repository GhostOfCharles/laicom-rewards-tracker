<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS - Login Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Full-screen background photo with a dark blue overlay */
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        body {
            background-image:
                linear-gradient(rgba(15, 35, 90, 0.75), rgba(15, 35, 90, 0.75)),
                url('{{ asset("images/portal-bg.jpg") }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Centered white card */
        .portal-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px 50px 30px 50px;
            width: 100%;
            max-width: 440px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        }

        /* Combined logo + LRTS image */
        .logo-header {
            display: block;
            margin: 0 auto 10px auto;
            height: 70px;      /* adjust if the image feels too small/large */
            width: auto;
        }

        /* Subtitle under logo */
        .subtitle {
            color: #333;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 30px;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            padding: 8px 0;
        }

        /* Pill buttons */
        .btn-portal {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background-color: #c8102e;
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1rem;
            letter-spacing: 1px;
            width: 100%;
            padding: 12px 0;
            margin-bottom: 12px;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .btn-portal:hover {
            background-color: #ed1b24;
            color: white;
        }

        /* Footer text inside card */
        .card-footer-text {
            margin-top: 25px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #222;
            letter-spacing: 1px;
            line-height: 1.6;
        }
        .card-footer-text .small-line {
            color: #555;
            font-weight: 500;
            font-size: 0.65rem;
        }
    </style>
</head>
<body>

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