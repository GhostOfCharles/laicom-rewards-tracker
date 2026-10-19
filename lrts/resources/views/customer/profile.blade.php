@extends('layouts.auth')
@section('title', 'Account settings')
@section('auth-back', route('customer.dashboard'))
@section('auth-heading', 'Account settings')
@section('auth-description', 'Manage your details and password.')
@section('content')
@if(!$user->must_change_password)
<form method="POST" data-auth-form action="{{ route('customer.profile.update') }}">@csrf @method('PATCH')
<x-form-field name="name" label="Full name" :value="$user->name" required maxlength="255" />
<x-form-field name="store_name" label="Store name" :value="$user->store_name" required maxlength="255" />
<x-form-field name="phone_number" label="Contact number" :value="$user->phone_number" required inputmode="tel" />
<x-auth-submit label="Save details" />
</form><hr>
@endif
<form method="POST" data-auth-form action="{{ route('customer.password') }}">@csrf @method('PUT')
<x-form-field name="current_password" label="Current password" type="password" required autocomplete="current-password" />
<x-form-field name="password" label="New password" type="password" required autocomplete="new-password" />
<p class="small text-muted">Use at least 8 characters with uppercase, lowercase, numbers and a symbol.</p>
<x-form-field name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />
<x-auth-submit label="Change password" />
</form>
@endsection
