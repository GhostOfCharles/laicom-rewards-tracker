@extends('layouts.admin')

@section('content')
    <div class="laicom-card p-3 mb-3">
        <h5 class="fw-bold border-bottom border-dark pb-2">CLAIM CODE LOOKUP</h5>
        <form method="GET" action="{{ route('admin.claims') }}" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input class="form-control border-dark rounded-0 flex-grow-1" name="code" value="{{ $code }}" placeholder="Enter a claim code">
            <button class="btn laicom-btn-primary rounded-0" type="submit"><i class="bi bi-search me-1"></i>LOOK UP</button>
        </form>
    </div>

    <nav class="nav nav-pills flex-wrap gap-2 mb-3" aria-label="Claim states">
        @foreach (['awaiting' => 'AWAITING RELEASE', 'available' => 'AVAILABLE', 'confirmation' => 'AWAITING CUSTOMER CONFIRMATION', 'completed' => 'COMPLETED', 'voided_expired' => 'VOIDED AND EXPIRED'] as $key => $label)
            <a class="nav-link border border-dark rounded-0 fw-bold {{ $tab === $key ? 'active' : 'text-dark bg-white' }}" href="{{ route('admin.claims', ['tab' => $key]) }}">{{ $label }} <span class="badge text-bg-light border border-dark ms-1">{{ $counts[$key] }}</span></a>
        @endforeach
    </nav>

    <div class="d-grid gap-3">
        @forelse ($groups as $groupKey => $rewards)
            @php
                $receipt = $rewards->first()->receipt;
                $customer = $rewards->first()->user;
                $code = $rewards->first()->claim_code;
                $expiresAt = $rewards->whereNotNull('expires_at')->min('expires_at');
                $hasExpiredProduct = $rewards->contains(fn ($reward) => $reward->premiumProduct?->isExpired());
                $modalId = 'releaseClaim' . md5($groupKey);
            @endphp
            <article class="laicom-card p-3">
                <div class="d-flex justify-content-between align-items-start gap-2 border-bottom border-dark pb-2 mb-2">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $customer->name ?? 'Unknown customer' }} <span class="text-muted fw-normal">| {{ $customer->store_name ?? 'No store name' }}</span></h6>
                        <div class="small text-muted">{{ $customer->phone_number ?? 'No phone number' }} · Order {{ $receipt?->salesman_order_number }}</div>
                    </div>
                    <span class="lrts-badge {{ in_array($tab, ['awaiting', 'confirmation'], true) ? 'lrts-badge-pending' : (in_array($tab, ['completed', 'voided_expired'], true) ? 'lrts-badge-resolved' : 'lrts-badge-open') }}">{{ strtoupper(str_replace('_', ' ', $tab)) }}</span>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-5">
                        <div class="small fw-bold text-muted mb-1">PURCHASED ITEMS</div>
                        @foreach ($receipt?->items ?? [] as $item)
                            <div class="small">{{ $item->quantity }}x {{ $item->product_name }}</div>
                        @endforeach
                    </div>
                    <div class="col-12 col-lg-7">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 laicom-table">
                                <thead><tr><th>REWARD</th><th>QTY</th><th>CLAIM CODE</th><th>REQUESTED</th><th>EXPIRES</th></tr></thead>
                                <tbody>
                                    @foreach ($rewards as $reward)
                                        <tr>
                                            <td>{{ $reward->premiumProduct?->name ?? $reward->reward_product_name ?? 'Premium reward' }}</td>
                                            <td>{{ $reward->reward_quantity }}</td>
                                            <td class="fw-bold">{{ $reward->claim_code ?? 'WALK-IN' }}</td>
                                            <td>{{ $reward->claim_requested_at?->format('M d, Y g:i A') ?? '—' }}</td>
                                            <td>{{ $reward->expires_at?->format('M d, Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @if ($hasExpiredProduct)<div class="alert alert-danger border-dark rounded-0 small fw-bold mt-3 mb-0">Release blocked because a linked premium product has expired.</div>@endif
                @if (in_array($tab, ['awaiting', 'available'], true))
                    <div class="text-end mt-3">
                        @if (!$hasExpiredProduct)
                            <button class="btn laicom-btn-primary rounded-0" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"><i class="bi bi-check-lg me-1"></i>CONFIRM RELEASE</button>
                        @else
                            <button class="btn btn-secondary rounded-0" disabled>CONFIRM RELEASE</button>
                        @endif
                    </div>
                    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" action="{{ $code ? route('admin.claims.release', $code) : route('admin.claims.release-receipt', $receipt) }}" class="modal-content laicom-modal rounded-0">
                                @csrf
                                <div class="modal-header text-white rounded-0"><h5 class="modal-title fw-bold">CONFIRM REWARD RELEASE</h5><button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                <div class="modal-body"><p class="small">Confirm that the reward items were physically handed to {{ $customer->name ?? 'the customer' }}.</p><label class="form-label fw-bold small" for="note{{ md5($groupKey) }}">OPTIONAL NOTE</label><input id="note{{ md5($groupKey) }}" class="form-control border-dark rounded-0" name="release_note" maxlength="255"></div>
                                <div class="modal-footer"><button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button><button class="btn laicom-btn-primary rounded-0" type="submit">CONFIRM RELEASE</button></div>
                            </form>
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="laicom-card p-4 text-center text-muted fw-bold">NO CLAIMS FOUND FOR THIS VIEW.</div>
        @endforelse
    </div>
@endsection
