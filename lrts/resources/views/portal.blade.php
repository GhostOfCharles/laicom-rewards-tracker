@extends('layouts.auth')

@section('title', 'Welcome')
@section('auth-description', 'Select your role to continue.')
@section('auth-page-class', 'lrts-portal-page')
@section('auth-card-class', 'portal-card')

@section('content')
    <nav class="portal-actions" aria-label="Choose your role">
        <a href="{{ route('login.customer') }}" class="auth-primary portal-role-button">
            <i class="bi bi-people-fill portal-role-icon" aria-hidden="true"></i>
            <span>Customer</span>
            <i class="bi bi-chevron-right portal-role-chevron" aria-hidden="true"></i>
        </a>
        <a href="{{ route('login.admin') }}" class="auth-primary portal-role-button portal-role-button--admin">
            <i class="bi bi-gear-fill portal-role-icon" aria-hidden="true"></i>
            <span>Admin</span>
            <i class="bi bi-chevron-right portal-role-chevron" aria-hidden="true"></i>
        </a>
    </nav>
@endsection
