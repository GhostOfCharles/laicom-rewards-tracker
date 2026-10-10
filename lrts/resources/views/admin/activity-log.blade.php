@extends('layouts.admin')

@section('content')
    <div class="laicom-card p-3 mb-3">
        <h5 class="fw-bold border-bottom border-dark pb-2">ACTIVITY LOG FILTERS</h5>
        <form method="GET" action="{{ route('admin.activity-log') }}" class="row g-2 align-items-end">
            <div class="col-6 col-lg-2"><label class="form-label small fw-bold">FROM</label><input class="form-control border-dark rounded-0" type="date" name="from" value="{{ $validated['from'] ?? '' }}"></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-bold">TO</label><input class="form-control border-dark rounded-0" type="date" name="to" value="{{ $validated['to'] ?? '' }}"></div>
            <div class="col-12 col-lg-3"><label class="form-label small fw-bold">USER</label><select class="form-select border-dark rounded-0" name="user_id"><option value="">All users</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected(($validated['user_id'] ?? '') == $user->id)>{{ $user->name }} ({{ $user->role }})</option>@endforeach</select></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-bold">CATEGORY</label><select class="form-select border-dark rounded-0" name="category"><option value="">All</option>@foreach ($categories as $category)<option value="{{ $category }}" @selected(($validated['category'] ?? '') === $category)>{{ strtoupper($category) }}</option>@endforeach</select></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-bold">SEARCH</label><input class="form-control border-dark rounded-0" name="q" value="{{ $validated['q'] ?? '' }}" maxlength="100"></div>
            <div class="col-12 col-lg-1"><button class="btn laicom-btn-primary rounded-0 w-100">FILTER</button></div>
        </form>
    </div>
    <div class="laicom-card p-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 laicom-table">
                <thead><tr><th>WHEN</th><th>WHO</th><th>ACTION</th><th>DESCRIPTION</th><th>IP</th><th>DETAILS</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at?->format('M d, Y g:i:s A') }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}<div class="small text-muted">{{ strtoupper($log->actor_role) }}</div></td>
                            <td><span class="badge text-bg-light border border-dark rounded-0">{{ $log->action }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                            <td>@if($log->properties)<details><summary>View</summary><pre class="small mt-2 mb-0">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>@else — @endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted fw-bold">NO ACTIVITY MATCHES THESE FILTERS.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-2">{{ $logs->links('pagination::simple-bootstrap-5') }}</div>
    </div>
@endsection
