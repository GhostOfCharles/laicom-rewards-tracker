@extends('layouts.auth')

@section('title', 'Customer Login')
@section('auth-back', route('portal'))
@section('auth-heading', 'Welcome Back')
@section('auth-description', 'Sign in to access your rewards.')

@section('content')
    <form action="{{ route('login.customer.submit') }}" method="POST" class="auth-form" data-auth-form>
        @csrf

        <x-auth-field
            name="email"
            type="email"
            label="Email"
            icon="envelope-fill"
            autocomplete="username"
            :value="old('email')"
            required
            autofocus
        />

        <x-auth-field
            name="password"
            type="password"
            label="Password"
            icon="lock-fill"
            autocomplete="current-password"
            required
        />

        @if(session('login_challenge'))<x-auth-captcha />@endif

        <x-auth-submit label="Sign In" loading-label="Signing in…" />
    </form>
    @if(config('lrts.password_reset_enabled') && !in_array(config('mail.default'), ['log','array']))<p class="auth-switch"><a href="{{ route('password.request') }}">Forgot password?</a></p>@endif

    <p class="auth-switch">Don't have an account? <a href="{{ route('register.customer') }}">Sign up</a></p>
@endsection
