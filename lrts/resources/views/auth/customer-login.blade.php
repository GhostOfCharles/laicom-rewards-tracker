<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS - Customer Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
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

        .login-card {
            background-color: #ffffff;
            border-radius: 14px;
            padding: 35px 40px 30px 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo-header {
            display: block;
            margin: 0 auto 8px auto;
            max-width: 65%;
            height: auto;
        }

        /* Subtitle — now navy blue */
        .subtitle {
            color: #12284c;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 2px;
            margin-top: 4px;
            margin-bottom: 25px;
        }

        .welcome-title {
            color: #12284c;
            font-weight: 900;
            font-size: 1.35rem;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        /* Divider — thinner now */
        .title-divider {
            width: 40px;
            height: 1px;
            background-color: #12284c;
            margin: 0 auto 25px auto;
        }

        .input-icon {
            position: relative;
            margin-bottom: 16px;
        }
        .input-icon i {
            position: absolute;
            top: 50%;
            left: 22px;
            transform: translateY(-50%);
            color: #12284c;
            font-size: 1.05rem;
            pointer-events: none;
        }
        .input-icon input {
            width: 100%;
            border: none;
            background-color: #f1f1f1;
            border-radius: 50px;
            padding: 14px 20px 14px 52px;
            font-size: 0.9rem;
            color: #333;
            font-weight: 500;
        }
        .input-icon input:focus {
            background-color: #e8e8e8;
            outline: none;
            box-shadow: 0 0 0 2px rgba(18, 40, 76, 0.2);
        }
        .input-icon input::placeholder {
            color: #555;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .btn-login {
            display: block;
            width: 100%;
            background-color: #12284c;
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: 3px;
            padding: 14px 0;
            margin-top: 10px;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .btn-login:hover {
            background-color: #032665;   /* ← new hover color */
            color: white;
        }

        .signup-link {
            font-size: 0.8rem;
            font-weight: 700;
            margin-top: 22px;
            display: block;
            color: #12284c;
            letter-spacing: 0.8px;
        }
        .signup-link a {
            color: #2b5fd9;
            text-decoration: underline;
            font-weight: 700;
        }
        .signup-link a:hover {
            color: #1c47a8;
        }

        .card-footer-text {
            margin-top: 28px;
            font-size: 0.65rem;
            font-weight: 800;
            color: #12284c;
            letter-spacing: 1.8px;
            line-height: 1.7;
        }
        .card-footer-text .small-line {
            color: #556;
            font-weight: 600;
            font-size: 0.6rem;
            letter-spacing: 1.5px;
        }

        .alert-danger {
            border-radius: 8px;
            font-size: 0.75rem;
            text-align: left;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="{{ asset('images/laicom-logo-header.png') }}" alt="LRTS" class="logo-header">
        <div class="subtitle">LAICOM REWARDS TRACKER SYSTEM</div>

        <h5 class="welcome-title">WELCOME, CUSTOMER</h5>
        <div class="title-divider"></div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.customer.submit') }}" method="POST">
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

        <span class="signup-link">NOT A MEMBER? <a href="{{ route('register.customer') }}">SIGN UP</a></span>

        <div class="card-footer-text">
            LAICOM SALES &amp; PROMOTIONS, INC.<br>
            <span class="small-line">REWARDS TODAY. STRONGER TOMORROW.</span>
        </div>
    </div>
</body>
</html>