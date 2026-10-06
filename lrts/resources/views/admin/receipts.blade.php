@extends('layouts.admin')

@section('content')
<div class="mb-3 lrts-receipts-page">
    <div class="d-flex align-items-center mb-2 lrts-receipts-filter">
        <label class="fw-bold small me-2 mb-0" for="receiptStatusFilter">SORT BY:</label>
        <select id="receiptStatusFilter" class="form-select form-select-sm border-dark rounded-0 w-auto fw-bold" onchange="window.location.href='{{ route('admin.receipts') }}?status=' + this.value">
            <option value="">ALL RECEIPTS</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>PROCESSING</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>APPROVED</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>REJECTED</option>
        </select>
    </div>
    
    <div class="lrts-receipt-page-list">
        @if(session('success'))
            <div class="alert alert-success py-2 fw-bold small rounded-0">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger py-2 fw-bold small rounded-0">{{ $errors->first() }}</div>
        @endif

        <div class="lrts-receipt-list">
                @forelse ($receipts as $receipt)
                <article class="lrts-receipt-card">
                    <div class="lrts-receipt-card-icon" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i></div>
                    <div class="lrts-receipt-card-details">
                        <div class="lrts-receipt-detail-row"><span>ORDER NO.</span><strong>{{ $receipt->salesman_order_number }}</strong></div>
                        <div class="lrts-receipt-detail-row"><span>CUSTOMER</span><strong>{{ $receipt->user->name ?? 'Unknown' }}</strong></div>
                        <div class="lrts-receipt-detail-row"><span>DATE SUBMITTED</span><strong>{{ $receipt->submitted_at?->format('m/d/Y') ?? '-' }}</strong></div>
                    </div>
                    <div class="lrts-receipt-card-modal-actions">
                        <button class="btn lrts-receipt-action-view" data-bs-toggle="modal" data-bs-target="#viewProductsModal{{ $receipt->id }}"><i class="bi bi-box-seam-fill" aria-hidden="true"></i><span>VIEW PRODUCTS</span></button>
                        <button class="btn lrts-receipt-action-view" data-bs-toggle="modal" data-bs-target="#viewRewardsModal{{ $receipt->id }}"><i class="bi bi-gift-fill" aria-hidden="true"></i><span>{{ $receipt->status === 'pending' ? 'VIEW PENDING REWARDS' : ($receipt->status === 'approved' ? 'VIEW CLAIMABLE REWARDS' : 'VIEW REWARDS') }}</span></button>
                    </div>
                    <div class="lrts-receipt-card-status-actions">
                        <div class="lrts-receipt-list-status is-{{ $receipt->status }}"><span>STATUS</span><strong>{{ $receipt->status === 'pending' ? 'PROCESSING' : strtoupper($receipt->status) }}</strong></div>
                        @if($receipt->status === 'approved' && $receipt->earnedRewards->contains(fn ($reward) => $reward->premiumProduct?->isExpired()))
                            <div class="lrts-receipt-expiry-warning"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span>EXPIRED REWARD PRODUCT</span></div>
                        @endif
                        @if($receipt->status === 'pending')
                            <form action="{{ route('admin.receipts.approve', $receipt->id) }}" method="POST" onsubmit="return confirm('Approve this receipt and deduct reward stock?');">
                                @csrf
                                <button type="submit" class="btn lrts-receipt-action-approve"><i class="bi bi-check-lg" aria-hidden="true"></i><span>APPROVE</span></button>
                            </form>
                            <form action="{{ route('admin.receipts.reject', $receipt->id) }}" method="POST" onsubmit="return confirm('Reject this receipt?');">
                                @csrf
                                <button type="submit" class="btn lrts-receipt-action-reject"><i class="bi bi-x-lg" aria-hidden="true"></i><span>REJECT</span></button>
                            </form>
                        @endif
                    </div>
                </article>

                @push('modals')
                <div class="modal fade" id="viewProductsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable lrts-receipt-modal-dialog">
                        <div class="modal-content lrts-receipt-modal">
                            <div class="modal-header lrts-receipt-modal-header">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-box-seam-fill fs-5" aria-hidden="true"></i>
                                    <div>
                                        <h6 class="modal-title fw-bold mb-0">PURCHASED PRODUCTS</h6>
                                        <div class="small">Order #{{ $receipt->salesman_order_number }}  |  {{ $receipt->items->count() }} {{ \Illuminate\Support\Str::plural('item', $receipt->items->count()) }}</div>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body lrts-receipt-modal-body">
                                <div class="lrts-receipt-meta mb-3">
                                    <div class="lrts-receipt-meta-item"><span>CUSTOMER</span><strong>{{ $receipt->user->name ?? 'Unknown' }}</strong></div>
                                    <div class="lrts-receipt-meta-item"><span>ORDER NO.</span><strong>{{ $receipt->salesman_order_number }}</strong></div>
                                    <div class="lrts-receipt-meta-item"><span>DATE SUBMITTED</span><strong>{{ $receipt->submitted_at?->format('M d, Y') ?? '-' }}</strong></div>
                                    <div class="lrts-receipt-meta-item"><span>RECEIPT STATUS</span><strong><span class="lrts-receipt-status is-{{ $receipt->status }}">{{ $receipt->status === 'pending' ? 'PROCESSING' : strtoupper($receipt->status) }}</span></strong></div>
                                </div>
                                <div class="lrts-receipt-table-title"><i class="bi bi-basket2 me-1" aria-hidden="true"></i>ITEMS ON THIS RECEIPT</div>
                                <table class="table table-sm align-middle mb-0 lrts-receipt-items-table">
                                    <thead><tr><th>PRODUCT NAME</th><th class="text-center">QUANTITY</th></tr></thead>
                                    <tbody>
                                    @forelse($receipt->items as $item)
                                        @php $itemImage = $receiptProductImages->get($item->product_name)?->image_path; @endphp
                                        <tr>
                                            <td><div class="lrts-receipt-product-cell">
                                                @if ($itemImage)<img src="{{ asset('storage/' . $itemImage) }}" alt="" onerror="this.remove()" class="lrts-receipt-product-image">@endif
                                                <span>{{ $item->product_name }}</span>
                                            </div></td>
                                            <td class="text-center"><span class="lrts-receipt-quantity">x{{ number_format($item->quantity) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2"><div class="lrts-receipt-empty"><i class="bi bi-basket" aria-hidden="true"></i><span>NO PURCHASED PRODUCTS RECORDED.</span></div></td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer lrts-receipt-modal-footer">
                                <button type="button" class="btn lrts-receipt-close" data-bs-dismiss="modal">CLOSE</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endpush

                @push('modals')
                @php
                    $receiptRewards = collect();
                    if ($receipt->status === 'pending') {
                        $receiptRewards = collect($receipt->calculated_rewards)->map(fn ($reward) => [
                            'name' => $reward['name'],
                            'image_path' => $reward['image_path'] ?? null,
                            'quantity' => (int) $reward['quantity'],
                            'status' => 'PENDING',
                        ]);
                    } elseif ($receipt->status === 'approved') {
                        $receiptRewards = $receipt->earnedRewards->groupBy('premium_product_id')->map(function ($rewards) {
                            $reward = $rewards->first();
                            return [
                                'name' => $reward->premiumProduct?->name ?? 'Premium product',
                                'image_path' => $reward->premiumProduct?->image_path,
                                'quantity' => (int) $rewards->sum('reward_quantity'),
                                'status' => $rewards->contains(fn ($item) => $item->claim_status !== 'claimed') ? 'AVAILABLE' : 'CLAIMED',
                            ];
                        })->values();
                    }
                    $rewardTypeCount = $receiptRewards->count();
                    $rewardQuantityCount = (int) $receiptRewards->sum('quantity');
                @endphp
                <div class="modal fade" id="viewRewardsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable lrts-receipt-modal-dialog">
                        <div class="modal-content lrts-receipt-modal">
                            <div class="modal-header lrts-receipt-modal-header">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-gift-fill fs-5" aria-hidden="true"></i>
                                    <div>
                                        <h6 class="modal-title fw-bold mb-0">{{ $receipt->status === 'pending' ? 'PENDING REWARDS' : ($receipt->status === 'approved' ? 'CLAIMABLE REWARDS' : 'REWARDS NOT AVAILABLE') }}</h6>
                                        <div class="small">Order #{{ $receipt->salesman_order_number }}  |  {{ $rewardTypeCount }} {{ \Illuminate\Support\Str::plural('reward type', $rewardTypeCount) }}</div>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body lrts-receipt-modal-body">
                                <div class="lrts-receipt-meta mb-3">
                                    <div class="lrts-receipt-meta-item"><span>CUSTOMER</span><strong>{{ $receipt->user->name ?? 'Unknown' }}</strong></div>
                                    <div class="lrts-receipt-meta-item"><span>ORDER NO.</span><strong>{{ $receipt->salesman_order_number }}</strong></div>
                                    <div class="lrts-receipt-meta-item"><span>DATE SUBMITTED</span><strong>{{ $receipt->submitted_at?->format('M d, Y') ?? '-' }}</strong></div>
                                    <div class="lrts-receipt-meta-item"><span>RECEIPT STATUS</span><strong><span class="lrts-receipt-status is-{{ $receipt->status }}">{{ $receipt->status === 'pending' ? 'PROCESSING' : strtoupper($receipt->status) }}</span></strong></div>
                                </div>
                                @if ($receipt->status === 'pending')
                                    <div class="lrts-reward-notice is-pending"><i class="bi bi-clock-history" aria-hidden="true"></i><span>These rewards will be available once the receipt has been approved.</span></div>
                                @elseif ($receipt->status === 'approved')
                                    <div class="lrts-reward-notice is-approved"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>This receipt is approved. Unclaimed rewards are available to the customer.</span></div>
                                @else
                                    <div class="lrts-reward-notice is-rejected"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span>This receipt was rejected. Rewards are not available to the customer.</span></div>
                                @endif
                                <div class="lrts-receipt-table-title"><i class="bi bi-gift me-1" aria-hidden="true"></i>REWARD DETAILS <span>{{ number_format($rewardQuantityCount) }} {{ \Illuminate\Support\Str::plural('reward', $rewardQuantityCount) }}</span></div>
                                <table class="table table-sm align-middle mb-0 lrts-receipt-items-table">
                                    <thead><tr><th>PREMIUM ITEM</th><th class="text-center">QUANTITY</th><th class="text-center">STATUS</th></tr></thead>
                                    <tbody>
                                    @forelse ($receiptRewards as $reward)
                                        <tr>
                                            <td><div class="lrts-receipt-product-cell">
                                                @if ($reward['image_path'])<img src="{{ asset('storage/' . $reward['image_path']) }}" alt="" onerror="this.remove()" class="lrts-receipt-product-image">@endif
                                                <span>{{ $reward['name'] }}</span>
                                            </div></td>
                                            <td class="text-center"><span class="lrts-receipt-quantity">x{{ number_format($reward['quantity']) }}</span></td>
                                            <td class="text-center"><span class="lrts-reward-status is-{{ strtolower($reward['status']) }}">{{ $reward['status'] }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3"><div class="lrts-receipt-empty"><i class="bi bi-gift" aria-hidden="true"></i><span>{{ $receipt->status === 'pending' ? 'NO PENDING REWARDS FOR THIS RECEIPT.' : 'NO CLAIMABLE REWARDS FOR THIS RECEIPT.' }}</span></div></td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer lrts-receipt-modal-footer">
                                <button type="button" class="btn lrts-receipt-close" data-bs-dismiss="modal">CLOSE</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endpush

                @empty
                <div class="lrts-receipts-empty">NO RECEIPTS MATCH THIS FILTER.</div>
                @endforelse
        </div>
    </div>
</div>
@stack('modals')
@endsection
