@extends('layouts.auth')
@section('title', 'Notifications')
@section('auth-back', route('customer.dashboard'))
@section('auth-heading', 'Notifications')
@section('auth-description', 'Updates about your receipts, rewards and support.')
@section('content')
@forelse($notifications as $notification)
<article class="border-bottom py-3"><strong>{{ $notification->title }}</strong><p class="small my-2">{{ $notification->body }}</p><time class="small text-muted">{{ $notification->created_at?->format('M d, Y H:i') }}</time>@if(!$notification->read_at)<form method="POST" action="{{ route('customer.notifications.item.read', $notification) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Mark read</button></form>@endif</article>
@empty<x-empty-state title="No notifications yet" icon="bell" />@endforelse
{{ $notifications->links() }}
@endsection
