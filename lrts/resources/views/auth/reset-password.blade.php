@extends('layouts.auth')
@section('title','Choose a password')
@section('auth-back',route('login.customer'))
@section('auth-heading','Choose a password')
@section('auth-description','Use a strong, unique password.')
@section('content')<form method="POST" data-auth-form action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><x-form-field name="email" label="Email" type="email" :value="$email" required /><x-form-field name="password" label="New password" type="password" required /><x-form-field name="password_confirmation" label="Confirm password" type="password" required /><x-auth-submit label="Reset password" /></form>@endsection
