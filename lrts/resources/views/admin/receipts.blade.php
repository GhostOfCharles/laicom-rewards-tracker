@extends('layouts.admin')

@section('content')
<div class="lrts-receipts-page mb-3">
    @if(session('success'))<div class="alert alert-success py-2 fw-bold small rounded-0">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger py-2 fw-bold small rounded-0">{{ $errors->first() }}</div>@endif

    <form method="GET" action="{{ route('admin.receipts') }}" class="d-flex flex-wrap gap-2 align-items-end mb-3 lrts-receipts-filter">
        <div><label class="form-label fw-bold small mb-1" for="receiptStatusFilter">RECEIPT STATUS</label>
            <select id="receiptStatusFilter" name="status" class="form-select form-select-sm border-dark rounded-0 fw-bold">
                <option value="">ALL RECEIPTS</option>
                @foreach(['pending' => 'PROCESSING', 'approved' => 'APPROVED', 'rejected' => 'REJECTED', 'cancelled' => 'CANCELLED'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="form-label fw-bold small mb-1" for="claimFilter">REWARD STATUS</label>
            <select id="claimFilter" name="claim_filter" class="form-select form-select-sm border-dark rounded-0 fw-bold">
                @foreach(['all' => 'ALL REWARDS', 'awaiting_release' => 'AWAITING RELEASE', 'claimed' => 'RELEASED', 'none_claimed' => 'NOT RELEASED'] as $value => $label)
                    <option value="{{ $value }}" @selected(($claimFilter ?? 'all') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="form-label fw-bold small mb-1" for="receiptSearch">ORDER OR CUSTOMER</label>
            <input id="receiptSearch" name="search" value="{{ request('search') }}" maxlength="100" class="form-control form-control-sm border-dark rounded-0" placeholder="Search order or name">
        </div>
        <button class="btn btn-sm lrts-receipt-action-view" type="submit"><i class="bi bi-search"></i> FILTER</button>
        <a class="btn btn-sm btn-outline-dark rounded-0" href="{{ route('admin.receipts') }}">RESET</a>
    </form>

    <div class="lrts-receipt-list">
        @forelse ($receipts as $receipt)
            @php
                $statusLabel = $receipt->status === 'pending' ? 'PROCESSING' : strtoupper($receipt->status);
                $activeRewards = $receipt->earnedRewards->whereIn('claim_status', ['unclaimed', 'claim_requested']);
                $claimState = $receipt->earnedRewards->contains(fn($reward) => $reward->claim_status === 'claim_requested') ? 'CLAIM REQUESTED' : ($receipt->earnedRewards->contains(fn($reward) => $reward->claim_status === 'claimed') ? 'RELEASED' : ($activeRewards->isNotEmpty() ? 'AVAILABLE' : 'NO REWARDS'));
                $hasExpiredRewardProduct = $receipt->earnedRewards->contains(fn($reward) => $reward->premiumProduct?->isExpired()) || collect($receipt->calculated_reward_groups)->flatMap(fn($group) => $group['options'])->contains(fn($option) => $option['product_expired'] ?? false);
                $canApproveReceipt = collect($receipt->calculated_reward_groups)->every(fn($group) => collect($group['options'])->contains(fn($option) => $option['in_stock']));
            @endphp
            <article class="lrts-receipt-card">
                <div class="lrts-receipt-card-icon" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i></div>
                <div class="lrts-receipt-card-details">
                    <div class="lrts-receipt-detail-row"><span>ORDER NO.</span><strong>{{ $receipt->salesman_order_number }}</strong></div>
                    <div class="lrts-receipt-detail-row"><span>CUSTOMER</span><strong>{{ $receipt->user->name ?? 'Unknown' }}</strong></div>
                    <div class="lrts-receipt-detail-row"><span>DATE SUBMITTED</span><strong>{{ $receipt->submitted_at?->format('m/d/Y') ?? '—' }}</strong></div>
                    <div class="lrts-receipt-detail-row"><span>REWARD STATUS</span><strong>{{ $claimState }}</strong></div>
                </div>
                <div class="lrts-receipt-card-modal-actions">
                    <button class="btn lrts-receipt-action-view" data-bs-toggle="modal" data-bs-target="#viewProductsModal{{ $receipt->id }}"><i class="bi bi-box-seam-fill"></i><span>VIEW PRODUCTS</span></button>
                    <button class="btn lrts-receipt-action-view" data-bs-toggle="modal" data-bs-target="#viewRewardsModal{{ $receipt->id }}"><i class="bi bi-gift-fill"></i><span>VIEW REWARDS</span></button>
                </div>
                <div class="lrts-receipt-card-status-actions">
                    <div class="lrts-receipt-list-status is-{{ $receipt->status }}"><span>STATUS</span><strong>{{ $statusLabel }}</strong></div>
                    @if($hasExpiredRewardProduct)<div class="lrts-receipt-expiry-warning"><i class="bi bi-exclamation-triangle-fill"></i><span>WARNING: EXPIRED REWARD PRODUCT</span></div>@endif
                    @if ($receipt->has_duplicate_slip)<span class="badge bg-warning text-dark border border-dark rounded-0">POSSIBLE DUPLICATE SLIP</span>@endif
                    @if ($receipt->status === 'pending')
                        <button type="button" class="btn lrts-receipt-action-approve" data-bs-toggle="modal" data-bs-target="#reviewReceipt{{ $receipt->id }}"><i class="bi bi-check-lg"></i><span>REVIEW &amp; APPROVE</span></button>
                        <button type="button" class="btn lrts-receipt-action-reject" data-bs-toggle="modal" data-bs-target="#rejectReceipt{{ $receipt->id }}"><i class="bi bi-x-lg"></i><span>REJECT</span></button>
                    @elseif($receipt->status === 'rejected' && $receipt->rejection_reason)
                        <small class="text-danger fw-bold">{{ $receipt->rejection_reason }}</small>
                    @endif
                </div>
            </article>

            @push('modals')
            <div class="modal fade" id="viewProductsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable lrts-receipt-modal-dialog"><div class="modal-content lrts-receipt-modal">
                    <div class="modal-header lrts-receipt-modal-header"><div class="d-flex align-items-center gap-2"><i class="bi bi-box-seam-fill fs-5"></i><div><h6 class="modal-title fw-bold mb-0">PURCHASED PRODUCTS</h6><div class="small">Order #{{ $receipt->salesman_order_number }} · {{ $receipt->items->count() }} {{ \Illuminate\Support\Str::plural('item', $receipt->items->count()) }}</div></div></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body lrts-receipt-modal-body">
                        <div class="lrts-receipt-meta mb-3"><div class="lrts-receipt-meta-item"><span>CUSTOMER</span><strong>{{ $receipt->user->name ?? 'Unknown' }}</strong></div><div class="lrts-receipt-meta-item"><span>ORDER DATE</span><strong>{{ $receipt->order_date?->format('M d, Y') ?? '—' }}</strong></div><div class="lrts-receipt-meta-item"><span>SUBMITTED</span><strong>{{ $receipt->submitted_at?->format('M d, Y') ?? '—' }}</strong></div><div class="lrts-receipt-meta-item"><span>STATUS</span><strong>{{ $statusLabel }}</strong></div></div>
                        @if($receipt->customer_note)<p class="small mb-2"><strong>CUSTOMER NOTE:</strong> {{ $receipt->customer_note }}</p>@endif
                        <div class="d-flex justify-content-between align-items-center mb-2"><div class="lrts-receipt-table-title mb-0"><i class="bi bi-basket2 me-1"></i>ITEMS ON THIS RECEIPT</div><a class="btn btn-sm btn-outline-dark rounded-0" href="{{ route('admin.receipts.slip', $receipt) }}" target="_blank" rel="noopener"><i class="bi bi-image"></i> VIEW SLIP</a></div>
                        @if($receipt->has_duplicate_slip)<div class="alert alert-warning py-2 small rounded-0">This slip image matches another submitted receipt. Review the order details before deciding.</div>@endif
                        <table class="table table-sm align-middle mb-0 lrts-receipt-items-table"><thead><tr><th>PRODUCT NAME</th><th class="text-center">QUANTITY</th></tr></thead><tbody>
                            @forelse($receipt->items as $item)@php $itemImage = $receiptProductImages->get($item->product_name)?->image_path; @endphp<tr><td><div class="lrts-receipt-product-cell">@if($itemImage)<img src="{{ asset('storage/' . $itemImage) }}" alt="" onerror="this.remove()" class="lrts-receipt-product-image">@endif<span>{{ $item->product_name }}</span></div></td><td class="text-center"><span class="lrts-receipt-quantity">x{{ number_format($item->quantity) }}</span></td></tr>
                            @empty<tr><td colspan="2">No products were recorded.</td></tr>@endforelse
                        </tbody></table>
                    </div><div class="modal-footer lrts-receipt-modal-footer"><button type="button" class="btn lrts-receipt-close" data-bs-dismiss="modal">CLOSE</button></div>
                </div></div>
            </div>

            <div class="modal fade" id="viewRewardsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl lrts-receipt-modal-dialog"><div class="modal-content lrts-receipt-modal">
                    <div class="modal-header lrts-receipt-modal-header"><div class="d-flex align-items-center gap-2"><i class="bi bi-gift-fill fs-5"></i><div><h6 class="modal-title fw-bold mb-0">REWARD DETAILS &amp; HISTORY</h6><div class="small">Order #{{ $receipt->salesman_order_number }} · {{ $receipt->earnedRewards->count() }} reward type(s)</div></div></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body lrts-receipt-modal-body"><div class="lrts-receipt-meta mb-3"><div class="lrts-receipt-meta-item"><span>CUSTOMER</span><strong>{{ $receipt->user->name ?? 'Unknown' }}</strong></div><div class="lrts-receipt-meta-item"><span>ORDER NO.</span><strong>{{ $receipt->salesman_order_number }}</strong></div><div class="lrts-receipt-meta-item"><span>SUBMITTED</span><strong>{{ $receipt->submitted_at?->format('M d, Y') ?? '—' }}</strong></div><div class="lrts-receipt-meta-item"><span>RECEIPT STATUS</span><strong>{{ $statusLabel }}</strong></div></div>
                        @if($receipt->status === 'pending')<div class="alert alert-warning py-2 small rounded-0">Rewards are calculated after staff review and approval.</div>@elseif($receipt->status === 'rejected')<div class="alert alert-danger py-2 small rounded-0">Receipt rejected: {{ $receipt->rejection_reason ?: 'No reason recorded.' }}</div>@elseif($receipt->earnedRewards->isEmpty())<div class="alert alert-secondary py-2 small rounded-0">No rewards were issued for this receipt.</div>@endif
                        @if($receipt->earnedRewards->isNotEmpty())<div class="table-responsive"><table class="table table-sm align-middle lrts-receipt-items-table"><thead><tr><th>PREMIUM ITEM</th><th>QTY</th><th>STATUS</th><th>CLAIM CODE</th><th>DATES / STAFF</th><th>ACTIONS</th></tr></thead><tbody>
                        @foreach($receipt->earnedRewards as $reward)<tr><td><div class="lrts-receipt-product-cell">@if($reward->premiumProduct?->image_path)<img src="{{ asset('storage/' . $reward->premiumProduct->image_path) }}" alt="" class="lrts-receipt-product-image">@endif<span>{{ $reward->premiumProduct?->name ?? $reward->reward_product_name ?? $reward->promotion_title ?? 'Premium reward' }}</span></div></td><td>{{ number_format($reward->reward_quantity) }}</td><td><span class="lrts-reward-status is-{{ strtolower($reward->claim_status) }}">{{ strtoupper(str_replace('_', ' ', $reward->claim_status)) }}</span></td><td class="small">{{ $reward->claim_code ?? '—' }}</td><td class="small">{{ $reward->claimed_at?->format('M d, Y H:i') ?? '—' }}<br>{{ $reward->releaser?->name ?? '' }}</td><td>
                            @if(in_array($reward->claim_status, ['unclaimed', 'claim_requested'], true))<div class="d-flex flex-wrap gap-1">
                                <form method="POST" action="{{ route('admin.rewards.release', $reward) }}">@csrf<button class="btn btn-sm btn-success rounded-0" type="submit">CONFIRM RELEASE</button></form>
                                <button class="btn btn-sm btn-danger rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#voidReward{{ $reward->id }}">VOID</button>
                            </div><form id="voidReward{{ $reward->id }}" class="collapse mt-2" method="POST" action="{{ route('admin.rewards.void', $reward) }}">@csrf<label class="small fw-bold">VOID REASON</label><input name="reason" minlength="5" maxlength="255" required class="form-control form-control-sm mb-1" placeholder="Reason"><label class="small"><input type="checkbox" name="return_stock" value="1" checked> Return reserved stock</label><button class="btn btn-sm btn-outline-danger rounded-0 mt-1" type="submit">CONFIRM VOID</button></form>@endif
                        </td></tr>@endforeach
                        </tbody></table></div>@endif
                        @if($receipt->activityLogs->isNotEmpty())<h6 class="fw-bold mt-3">ORDER ACTIVITY</h6><ul class="small mb-0">@foreach($receipt->activityLogs->sortByDesc('created_at') as $log)<li><strong>{{ $log->created_at?->format('M d, Y H:i') }}</strong> · {{ $log->description }} @if($log->user)({{ $log->user->name }})@endif</li>@endforeach</ul>@endif
                    </div><div class="modal-footer lrts-receipt-modal-footer"><button type="button" class="btn lrts-receipt-close" data-bs-dismiss="modal">CLOSE</button></div>
                </div></div>
            </div>

            @if($receipt->status === 'pending')
                <div class="modal fade" id="reviewReceipt{{ $receipt->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><form method="POST" action="{{ route('admin.receipts.approve', $receipt->id) }}" class="modal-content lrts-receipt-modal" data-approval-form>@csrf
                    <div class="modal-header lrts-receipt-modal-header"><div><h5 class="modal-title fw-bold">REVIEW RECEIPT</h5><small>Order #{{ $receipt->salesman_order_number }} · {{ $receipt->user->name ?? 'Unknown' }}</small></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body lrts-receipt-modal-body"><div class="d-flex gap-2 align-items-center mb-3"><a href="{{ route('admin.receipts.slip', $receipt) }}" target="_blank" rel="noopener"><img src="{{ route('admin.receipts.slip', $receipt) }}" alt="Submitted order slip" style="max-height:180px;max-width:100%;object-fit:contain" class="border"></a><span class="small text-muted">Select one applicable reward promotion per qualifying purchased product.</span></div>
                        @if($receipt->has_duplicate_slip)<div class="alert alert-warning py-2 small rounded-0">Duplicate slip hash detected. Compare the order number and date before approval.</div>@endif
                        @forelse($receipt->calculated_reward_groups as $group)
                            <fieldset class="border p-2 mb-3" data-reward-group><legend class="float-none w-auto px-1 small fw-bold">{{ $group['buy_product_name'] }} · {{ $group['purchased_quantity'] }} purchased</legend>
                                @foreach($group['options'] as $option)
                                    <label class="d-flex align-items-center gap-2 border p-2 mb-1 {{ $option['in_stock'] ? '' : 'opacity-50' }}"><input type="radio" name="selections[{{ $group['buy_product_name'] }}]" value="{{ $option['promotion_id'] }}" @disabled(!$option['in_stock']) @checked(!$group['needs_choice'] && $loop->first) @required($group['needs_choice'])><span class="flex-grow-1"><strong>{{ $option['premium_name'] }}</strong><small class="d-block">{{ $option['promotion_title'] ?: 'Promotion' }} · {{ $option['multiplier'] }} × {{ $option['promo_reward_quantity'] }} = {{ $option['reward_quantity'] }}</small></span><span class="badge {{ $option['in_stock'] ? 'bg-success' : 'bg-danger' }}">{{ !empty($option['product_expired']) ? 'EXPIRED' : $option['stock_available'] . ' IN STOCK' }}</span></label>
                                @endforeach
                            </fieldset>
                        @empty<div class="alert alert-info rounded-0">No active promotion qualifies on the order date. Approval will record the receipt without a reward.</div>@endforelse
                        @if(!$canApproveReceipt)<div class="alert alert-danger py-2 small rounded-0">At least one qualifying product has no available, unexpired reward option. Restock or update the promotion before approving this order.</div>@endif
                        <div class="small text-muted">Approval reserves the selected reward stock. Customer release is recorded separately when staff hands over the reward.</div>
                    </div><div class="modal-footer"><button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button><button type="submit" class="btn lrts-receipt-action-approve" @disabled(!$canApproveReceipt)>APPROVE &amp; RESERVE STOCK</button></div>
                </form></div></div>
                <div class="modal fade" id="rejectReceipt{{ $receipt->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admin.receipts.reject', $receipt->id) }}" class="modal-content rounded-0 border border-dark">@csrf<div class="modal-header bg-danger text-white"><h5 class="modal-title fw-bold">REJECT RECEIPT</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small">Order #{{ $receipt->salesman_order_number }} · {{ $receipt->user->name ?? 'Unknown' }}</p><label class="form-label fw-bold small">REASON (SENT TO CUSTOMER)</label><select class="form-select rounded-0 mb-2" data-rejection-preset><option value="">Choose a common reason…</option><option>Order number could not be verified.</option><option>The uploaded slip is unreadable.</option><option>The order date does not match the submitted details.</option><option>One or more listed products could not be verified.</option></select><textarea name="reason" required minlength="5" maxlength="500" rows="3" class="form-control rounded-0" placeholder="Explain what needs correction."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button><button class="btn btn-danger rounded-0 fw-bold" type="submit">REJECT RECEIPT</button></div></form></div></div>
            @endif
            @endpush
        @empty
            <div class="lrts-receipts-empty">NO RECEIPTS MATCH THIS FILTER.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $receipts->links() }}</div>
</div>
@stack('modals')
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-rejection-preset]').forEach(select => select.addEventListener('change', () => {
    if (select.value) select.closest('form').querySelector('textarea[name="reason"]').value = select.value;
}));
</script>
@endpush
