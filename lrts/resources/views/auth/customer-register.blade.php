<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS - Customer Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/laicom.css') }}">
</head>
<body class="lrts-auth-page" style="--lrts-auth-bg-image: url('{{ asset('images/portal-bg.jpg') }}');">
    <div class="login-card">
        <img src="{{ asset('images/laicom-logo-header.png') }}" alt="LRTS" class="logo-header">
        <div class="subtitle">LAICOM REWARDS TRACKER SYSTEM</div>

        <h5 class="welcome-title">CUSTOMER REGISTRATION</h5>
        <div class="title-divider"></div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.customer.submit') }}" method="POST">
            @csrf

            <div class="input-icon">
                <i class="bi bi-person-fill"></i>
                <input type="text" name="name" placeholder="FULL NAME" required value="{{ old('name') }}">
            </div>

            <div class="input-icon">
                <i class="bi bi-envelope-fill"></i>
                <input type="email" name="email" placeholder="EMAIL / USERNAME" required value="{{ old('email') }}">
            </div>

            <div class="input-icon">
                <i class="bi bi-shop"></i>
                <input type="text" name="store_name" placeholder="STORE NAME" value="{{ old('store_name') }}">
            </div>

            <div class="input-icon">
                <i class="bi bi-telephone-fill"></i>
                <input type="text" name="phone_number" id="phoneNumber" placeholder="09XX XXX XXXX" value="{{ old('phone_number') }}" inputmode="numeric" maxlength="13" pattern="09[0-9]{2} [0-9]{3} [0-9]{4}">
            </div>

            <div class="input-icon pw-input-wrap">
                <i class="bi bi-lock-fill"></i>
                <input type="password" name="password" id="registerPassword" class="pw-input" placeholder="PASSWORD" required autocomplete="new-password" aria-describedby="pwStrength">
                <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password" aria-pressed="false">SHOW</button>
            </div>
            <div class="pw-strength" id="pwStrength" style="display:none;" aria-live="polite">
                <div class="pw-strength-bars">
                    <span class="pw-bar" data-bar="1"></span>
                    <span class="pw-bar" data-bar="2"></span>
                    <span class="pw-bar" data-bar="3"></span>
                </div>
                <div class="pw-strength-label" id="pwStrengthLabel"></div>
                <ul class="pw-reqs" aria-label="Password requirements">
                    <li class="pw-req" data-req="length">8+ characters</li>
                    <li class="pw-req" data-req="uppercase">Uppercase</li>
                    <li class="pw-req" data-req="lowercase">Lowercase</li>
                    <li class="pw-req" data-req="number">Number</li>
                    <li class="pw-req" data-req="symbol">Symbol</li>
                </ul>
            </div>

            <div class="input-icon">
                <i class="bi bi-lock-fill"></i>
                <input type="password" name="password_confirmation" id="registerPasswordConfirmation" placeholder="CONFIRM PASSWORD" required autocomplete="new-password" aria-describedby="pwMatch">
            </div>
            <div class="pw-match" id="pwMatch" style="display:none;" aria-live="polite"></div>

            {{-- Cloudflare Turnstile widget (renders based on CAPTCHA_DRIVER in .env) --}}
            <div class="captcha-wrapper">
                <x-laracaptcha::widget />
            </div>

            <button type="submit" class="btn-login">REGISTER</button>
        </form>

        <span class="login-link">ALREADY A MEMBER? <a href="{{ route('login.customer') }}">LOGIN</a></span>

        <div class="card-footer-text">
            LAICOM SALES &amp; PROMOTIONS, INC.<br>
            <span class="small-line">REWARDS TODAY. STRONGER TOMORROW.</span>
        </div>
    </div>
    <script>
        (() => {
            const password = document.getElementById('registerPassword');
            const confirmation = document.getElementById('registerPasswordConfirmation');
            const phone = document.getElementById('phoneNumber');
            const strength = document.getElementById('pwStrength');
            const strengthLabel = document.getElementById('pwStrengthLabel');
            const bars = [...document.querySelectorAll('.pw-bar')];
            const requirements = [...document.querySelectorAll('.pw-req')];
            const match = document.getElementById('pwMatch');
            const toggle = document.getElementById('pwToggle');

            const updateStrength = () => {
                const value = password.value;
                if (!value) {
                    strength.style.display = 'none';
                    return;
                }

                strength.style.display = '';
                const checks = {
                    length: value.length >= 8,
                    uppercase: /[A-Z]/.test(value),
                    lowercase: /[a-z]/.test(value),
                    number: /[0-9]/.test(value),
                    symbol: /[^A-Za-z0-9]/.test(value),
                };
                requirements.forEach((item) => item.classList.toggle('is-met', checks[item.dataset.req]));

                const typeCount = [checks.uppercase || checks.lowercase, checks.number, checks.symbol].filter(Boolean).length;
                const strong = value.length >= 10 && checks.lowercase && checks.uppercase && checks.number && checks.symbol;
                const tier = strong ? 'strong' : (value.length < 8 || typeCount <= 1 ? 'weak' : 'medium');
                const filledBars = tier === 'strong' ? 3 : (tier === 'medium' ? 2 : 1);

                bars.forEach((bar, index) => {
                    bar.classList.remove('is-weak', 'is-medium', 'is-strong');
                    if (index < filledBars) bar.classList.add(`is-${tier}`);
                });
                strengthLabel.className = `pw-strength-label is-${tier}`;
                strengthLabel.textContent = `Your password is ${tier}`;
            };

            const updateMatch = () => {
                if (!password.value && !confirmation.value) {
                    match.style.display = 'none';
                    return;
                }

                match.style.display = '';
                const matches = password.value !== '' && confirmation.value !== '' && password.value === confirmation.value;
                match.className = `pw-match ${matches ? 'is-match' : 'is-mismatch'}`;
                match.textContent = matches ? 'Passwords match' : 'Passwords do not match';
            };

            const formatPhone = () => {
                let digits = phone.value.replace(/\D/g, '');
                if (!digits) {
                    phone.value = '';
                    return;
                }

                if (digits.startsWith('9')) {
                    digits = `0${digits}`;
                } else if (digits !== '0' && !digits.startsWith('09')) {
                    digits = `09${digits}`;
                }
                digits = digits.slice(0, 11);
                phone.value = [digits.slice(0, 4), digits.slice(4, 7), digits.slice(7, 11)].filter(Boolean).join(' ');
            };

            password.addEventListener('input', () => { updateStrength(); updateMatch(); });
            confirmation.addEventListener('input', updateMatch);
            phone.addEventListener('input', formatPhone);
            toggle.addEventListener('click', () => {
                const showing = password.type === 'password';
                password.type = showing ? 'text' : 'password';
                toggle.textContent = showing ? 'HIDE' : 'SHOW';
                toggle.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', String(showing));
            });

            formatPhone();
            updateStrength();
            updateMatch();
        })();
    </script>
</body>
</html>
