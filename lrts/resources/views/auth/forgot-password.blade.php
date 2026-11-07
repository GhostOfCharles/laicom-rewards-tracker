@extends('layouts.auth')
@section('title','Reset password')
@section('auth-back',route('login.customer'))
@section('auth-heading','Reset password')
@section('auth-description','Enter your email to request a reset link.')
@section('content')<form method="POST" data-auth-form action="{{ route('password.email') }}">@csrf<x-form-field name="email" label="Email" type="email" required /><x-auth-submit label="Send reset link" /></form>@endsection
