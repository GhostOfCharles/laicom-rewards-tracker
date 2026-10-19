<div class="lrts-receipts-page mb-3" data-refresh-region="receipts">
<form method="GET" action="{{ route('admin.receipts') }}" class="d-flex flex-wrap gap-2 align-items-end mb-3">
<div><label class="form-label" for="receiptStatusFilter">Receipt status</label><select id="receiptStatusFilter" name="status" class="form-select form-select-sm"><option value="">All receipts</option>@foreach(['pending'=>'Processing','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $value=>$label)<option value="{{ $value }}" @selected($currentStatus===$value)>{{ $label }}</option>@endforeach</select></div>
<div><label class="form-label" for="claimFilter">Reward status</label><select id="claimFilter" name="claim_filter" class="form-select form-select-sm">@foreach(['all'=>'All','awaiting_release'=>'Awaiting handover','awaiting_customer'=>'Awaiting customer','claimed'=>'Received','none_claimed'=>'None received'] as $value=>$label)<option value="{{ $value }}" @selected($claimFilter===$value)>{{ $label }}</option>@endforeach</select></div>
<div><label for="receiptSearch" class="form-label">Order or customer</label><input id="receiptSearch" name="search" value="{{ request('search') }}" maxlength="100" class="form-control form-control-sm" placeholder="Order or name"></div>
<button class="btn btn-sm rts-btn-primary">Filter</button><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.receipts') }}">Reset</a>
</form>
<div class="lrts-receipt-list">
@forelse($receipts as $receipt)
<article class="lrts-receipt-card"><div class="lrts-receipt-card-icon" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i></div><div class="lrts-receipt-card-details"><strong>{{ $receipt->salesman_order_number }}</strong><div>{{ $receipt->user->name ?? 'Unknown' }} &middot; {{ $receipt->user->store_name }}</div><small>{{ $receipt->submitted_at?->format('M d, Y H:i') }}</small><div><x-status-badge :status="$receipt->status" /></div>@if($receipt->has_duplicate_slip)<small class="text-warning">Possible duplicate slip</small>@endif @if($receipt->earnedRewards->contains(fn($r)=>$r->premiumProduct?->isExpired()))<small class="text-danger">WARNING: EXPIRED REWARD PRODUCT</small>@endif</div>
<div class="lrts-receipt-card-modal-actions"><button type="button" class="btn rts-btn-primary" data-receipt-panel="{{ route('admin.receipts.panel',$receipt) }}">{{ $receipt->status==='pending' ? 'Review receipt' : 'View details' }}</button></div></article>
@empty<x-empty-state title="No receipts match this filter" icon="receipt" />@endforelse
</div>{{ $receipts->links() }}
</div>
