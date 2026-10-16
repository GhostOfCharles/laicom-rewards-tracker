@extends('layouts.auth')

@section('title', 'Admin Login')
@section('auth-back', route('portal'))
@section('auth-heading', 'Admin Login')
@section('auth-description', 'Sign in to manage the system.')

@section('content')
    <form action="{{ route('login.admin.submit') }}" method="POST" class="auth-form" data-auth-form>
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

        <x-auth-submit label="Sign In" loading-label="Signing in…" />
    </form>
@endsection
