@extends('layouts.auth')
@section('title', 'Please wait')
@section('auth-back', route('portal'))
@section('auth-heading', 'Please wait')
@section('auth-description', 'Too many requests. Please try again shortly.')
@section('content')
<p class="text-center text-muted" role="status">You can try again in approximately {{ $retryAfter }} seconds.</p>
@endsection
