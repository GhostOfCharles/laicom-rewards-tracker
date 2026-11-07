@extends('layouts.auth')

@section('title', 'Create an Account')
@section('auth-back', route('login.customer'))
@section('auth-heading', 'Create an Account')
@section('auth-description', 'Register to start tracking your rewards.')

@section('content')
        <form action="{{ route('register.customer.submit') }}" method="POST" class="auth-form" data-auth-form>
            @csrf

            <x-auth-field
                name="name"
                label="Full Name"
                icon="person-fill"
                autocomplete="name"
                :value="old('name')"
                required
            />

            <x-auth-field
                name="email"
                type="email"
                label="Email / Username"
                icon="envelope-fill"
                autocomplete="username"
                :value="old('email')"
                required
            />

            <x-auth-field
                name="store_name"
                label="Store Name"
                icon="shop"
                autocomplete="organization"
                :value="old('store_name')"
                required
            />

            <x-auth-field
                name="phone_number"
                id="phoneNumber"
                label="Contact Number"
                icon="telephone-fill"
                placeholder="09XX XXX XXXX"
                autocomplete="tel-national"
                :value="old('phone_number')"
                inputmode="numeric"
                maxlength="13"
                pattern="09[0-9]{2} [0-9]{3} [0-9]{4}"
                required
            />

            <x-auth-field
                name="password"
                type="password"
                id="registerPassword"
                label="Password"
                icon="lock-fill"
                class="pw-input"
                autocomplete="new-password"
                described-by="pwStrength"
                toggle-id="pwToggle"
                required
            />
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

            <x-auth-field
                name="password_confirmation"
                type="password"
                id="registerPasswordConfirmation"
                label="Confirm Password"
                icon="lock-fill"
                autocomplete="new-password"
                described-by="pwMatch"
                required
            />
            <div class="pw-match" id="pwMatch" style="display:none;" aria-live="polite"></div>

            {{-- Cloudflare Turnstile widget (renders based on CAPTCHA_DRIVER in .env) --}}
            <div class="auth-captcha captcha-wrapper" aria-label="Security verification">
                <x-auth-captcha />
            </div>

            <x-auth-submit label="Create Account" loading-label="Creating account…" />
        </form>

        <p class="auth-switch">Already have an account? <a href="{{ route('login.customer') }}">Sign in</a></p>
@endsection

@push('scripts')
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
            formatPhone();
            updateStrength();
            updateMatch();
        })();
    </script>
@endpush
