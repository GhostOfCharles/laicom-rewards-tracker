@forelse($notifications as $notification)
    <div class="px-3 py-3 small border-bottom {{ $notification->read_at ? 'text-muted' : 'fw-bold' }}" data-notification-id="{{ $notification->id }}">
        <strong>{{ $notification->title }}</strong>
        <div>{{ $notification->body }}</div>
        <time class="text-muted">{{ $notification->created_at?->diffForHumans() }}</time>
    </div>
@empty
    <div class="px-3 py-3 small text-muted">You are all caught up.</div>
@endforelse
