    <!-- Dynamic View Order Modals -->
    @foreach ($receipts as $receipt)
    <div class="modal fade" id="viewOrderModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-dark border-2 rounded-0">
                <div class="modal-header border-bottom border-dark text-white">
                    <h6 class="modal-title fw-bold">ORDER DETAILS: {{ $receipt->salesman_order_number }}</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="row g-2 small border-bottom pb-2 mb-3"><div class="col-6"><strong>ORDER DATE</strong><br>{{ $receipt->order_date?->format('M d, Y') ?? '—' }}</div><div class="col-6"><strong>SUBMITTED</strong><br>{{ $receipt->submitted_at?->format('M d, Y H:i') ?? '—' }}</div><div class="col-6"><strong>STATUS</strong><br>{{ $receipt->status === 'pending' ? 'PROCESSING' : strtoupper($receipt->status) }}</div><div class="col-6"><strong>ORDER NUMBER</strong><br>{{ $receipt->salesman_order_number }}</div></div>
                    @if($receipt->slip_path)<div class="mb-3"><strong class="small">UPLOADED RECEIPT</strong><div><a href="{{ route('customer.receipts.slip', $receipt) }}" target="_blank" rel="noopener"><img src="{{ route('customer.receipts.slip', $receipt) }}" alt="Uploaded receipt" class="border mt-1 lrts-receipt-photo-preview"></a></div></div>@endif
                    @if($receipt->customer_note)<p class="small"><strong>YOUR NOTE:</strong> {{ $receipt->customer_note }}</p>@endif
                    <h6 class="fw-bold border-bottom border-dark pb-1 mb-2">PURCHASED PRODUCTS</h6><ul class="list-unstyled small fw-bold mb-3">@forelse($receipt->items as $item)<li>{{ $item->quantity }} × {{ $item->product_name }}</li>@empty<li class="text-muted">No items recorded.</li>@endforelse</ul>
                    <h6 class="fw-bold border-bottom border-dark pb-1 mb-2">REWARD STATUS</h6>
                    @if($receipt->status === 'pending')<div class="alert alert-warning py-2 small mb-2">Staff will review this receipt before rewards are selected.</div>@elseif($receipt->status === 'rejected')<div class="alert alert-danger py-2 small mb-2">{{ $receipt->rejection_reason ?: 'This order was rejected.' }}</div>@elseif($receipt->status === 'cancelled')<div class="alert alert-secondary py-2 small mb-2">This order was cancelled.</div>@endif
                    @forelse($receipt->earnedRewards as $reward)<div class="d-flex justify-content-between border-bottom py-2 small"><span>{{ $reward->reward_quantity }} × {{ $reward->premiumProduct?->name ?? $reward->reward_product_name ?? 'Reward item' }}</span><strong>{{ strtoupper(str_replace('_', ' ', $reward->claim_status)) }}</strong></div>@empty @if($receipt->status === 'approved')<div class="small text-muted">No promotions applied to this order.</div>@endif @endforelse
                    <h6 class="fw-bold mt-3 mb-2">Order timeline</h6><ol class="small mb-0">
                    <li>Submitted: {{ $receipt->submitted_at?->format('M d, Y H:i') ?? 'Recorded' }}</li>
                    @if($receipt->reviewed_at)<li>{{ ucfirst($receipt->status) }}: {{ $receipt->reviewed_at->format('M d, Y H:i') }}</li>@endif
                    @foreach($receipt->earnedRewards as $reward)
                    @if($reward->claim_requested_at)<li>Claim requested: {{ $reward->claim_requested_at->format('M d, Y H:i') }}</li>@endif
                    @if($reward->released_at)<li>Handed over: {{ $reward->released_at->format('M d, Y H:i') }}</li>@endif
                    @if($reward->claimed_at)<li>Receipt confirmed: {{ $reward->claimed_at->format('M d, Y H:i') }}</li>@endif
                    @endforeach</ol>
                </div>                <div class="modal-footer border-top-0 justify-content-end">
                    <button type="button" class="btn btn-outline-dark rounded-0 fw-bold" data-bs-dismiss="modal">CLOSE</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @foreach ($receipts as $receipt)
        <div class="modal fade" id="viewRewardsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content border border-dark border-2 rounded-0">
                <div class="modal-header border-bottom border-dark text-white"><h6 class="modal-title fw-bold">CLAIMABLE REWARDS: {{ $receipt->salesman_order_number }}</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">@forelse ($receipt->earnedRewards as $reward)<div class="d-flex justify-content-between align-items-center border-bottom py-2"><span class="fw-bold">{{ $reward->premiumProduct->name ?? 'Premium item' }}</span><span>{{ $reward->reward_quantity }} × <span class="badge {{ $reward->claim_status === 'claimed' ? 'bg-secondary' : ($reward->claim_status === 'released' ? 'bg-warning text-dark' : 'bg-success') }}">{{ $reward->claim_status === 'claimed' ? 'RECEIVED' : ($reward->claim_status === 'released' ? 'CONFIRM RECEIPT' : 'AVAILABLE') }}</span></span></div>@empty<p class="small text-muted mb-0">No claimable rewards are available for this receipt.</p>@endforelse</div>
                <div class="modal-footer border-top-0"><button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button></div>
            </div></div>
        </div>
    @endforeach

