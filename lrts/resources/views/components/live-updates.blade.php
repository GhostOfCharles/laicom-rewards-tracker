@props(['role', 'snapshot'])

<div class="lrts-live-notice" data-live-dashboard
     data-live-endpoint="{{ route($role . '.heartbeat') }}"
     data-live-fingerprint="{{ $snapshot['fingerprint'] }}"
     data-live-identity="{{ $role }}:{{ auth()->id() }}" hidden>
    <button type="button" class="btn btn-sm" data-live-refresh>New updates available, click to refresh</button>
    <a href="{{ route($role === 'admin' ? 'login.admin' : 'login.customer') }}" data-live-expired hidden>Session expired, please log in again</a>
    <span class="visually-hidden" role="status" aria-live="polite" data-live-status></span>
</div>
<div hidden data-live-actions>@stack('live-actions')</div>
<script src="{{ asset('js/live-updates.js') }}?v={{ filemtime(public_path('js/live-updates.js')) }}" defer></script>
