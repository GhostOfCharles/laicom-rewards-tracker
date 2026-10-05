@extends('layouts.admin')

@section('content')
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <label class="fw-bold small me-2 mb-0">SORT BY:</label>
        <select class="form-select form-select-sm border-dark rounded-0 w-auto fw-bold" onchange="window.location.href='{{ route('admin.receipts') }}?status=' + this.value">
            <option value="">ALL RECEIPTS</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>PROCESSING</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>APPROVED</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>REJECTED</option>
        </select>
    </div>
    
    <div class="laicom-card p-2">
        @if(session('success'))
            <div class="alert alert-success py-2 fw-bold small rounded-0">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger py-2 fw-bold small rounded-0">{{ $errors->first() }}</div>
        @endif

        <table class="table table-borderless mb-0">
            <tbody>
                @forelse ($receipts as $receipt)
                <tr class="border-bottom border-dark">
                    <td class="text-start pb-3">
                        <div class="fw-bold">ORDER NO: {{ $receipt->salesman_order_number }}</div>
                        <div class="small fw-bold mt-1">CUSTOMER: {{ $receipt->user->name ?? 'Unknown' }}</div>
                        <div class="small fw-bold">DATE SUBMITTED: {{ \Carbon\Carbon::parse($receipt->submitted_at)->format('m/d/Y') }}</div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-dark rounded-0 fw-bold me-2" data-bs-toggle="modal" data-bs-target="#viewProductsModal{{ $receipt->id }}">VIEW PRODUCTS</button>
                            <button class="btn btn-sm btn-outline-dark rounded-0 fw-bold" data-bs-toggle="modal" data-bs-target="#viewRewardsModal{{ $receipt->id }}">VIEW CLAIMABLE REWARDS</button>
                        </div>
                    </td>
                    <td class="text-end align-middle pb-3" style="width: 150px;">
                        <div class="badge {{ $receipt->status === 'approved' ? 'bg-success' : ($receipt->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }} border border-dark rounded-0 w-100 py-2 mb-2">STATUS: {{ $receipt->status === 'pending' ? 'PROCESSING' : strtoupper($receipt->status) }}</div>
                        @if($receipt->status === 'approved' && $receipt->earnedRewards->contains(fn ($reward) => $reward->premiumProduct?->isExpired()))
                            <div class="badge bg-danger border border-dark rounded-0 w-100 py-2 mb-2">WARNING: EXPIRED REWARD PRODUCT</div>
                        @endif
                        @if($receipt->status === 'pending')
                            <form action="{{ route('admin.receipts.approve', $receipt->id) }}" method="POST" class="mb-1" onsubmit="return confirm('Approve this receipt and deduct reward stock?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success rounded-0 w-100 fw-bold">APPROVE</button>
                            </form>
                            <form action="{{ route('admin.receipts.reject', $receipt->id) }}" method="POST" onsubmit="return confirm('Reject this receipt?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger rounded-0 w-100 fw-bold">REJECT</button>
                            </form>
                        @endif
                    </td>
                </tr>

                @push('modals')
                <!-- Dynamic View Products Modal for this specific loop iteration -->
                <div class="modal fade" id="viewProductsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content border-dark border-2 rounded-0">
                            <div class="modal-header border-bottom border-dark">
                                <h6 class="modal-title fw-bold">PURCHASED PRODUCTS | ORDER #: {{ $receipt->salesman_order_number }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-start">
                                <div class="small fw-bold mb-1">CUSTOMER: {{ $receipt->user->name ?? 'Unknown' }}</div>
                                <div class="small fw-bold mb-3">DATE SUBMITTED: {{ \Carbon\Carbon::parse($receipt->submitted_at)->format('m/d/Y') }}</div>
                                <h6 class="fw-bold border-bottom border-dark pb-1">LOGGED ITEMS:</h6>
                                <ul class="list-unstyled small fw-bold">
                                    @forelse($receipt->items as $item)
                                        <li>• {{ $item->quantity }}X {{ $item->product_name }}</li>
                                    @empty
                                        <li class="text-muted">No items recorded for this order.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="modal-footer border-top-0 justify-content-end">
                                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button>
                            </div>
                        </div>
                    </div>
                </div>

                @endpush

                @push('modals')
                <!-- Dynamic View Claimable Rewards Modal -->
                <div class="modal fade" id="viewRewardsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content border-dark border-2 rounded-0">
                            <div class="modal-header border-bottom border-dark">
                                <h6 class="modal-title fw-bold">CLAIMABLE REWARDS | ORDER #: {{ $receipt->salesman_order_number }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-start">
                                @if ($receipt->status === 'pending')
                                    @if (count($receipt->calculated_rewards))
                                        <p class="small text-muted">Calculated rewards will be issued after approval:</p>
                                        <ul class="list-group list-group-flush">
                                            @foreach ($receipt->calculated_rewards as $reward)
                                                <li class="list-group-item px-0 d-flex justify-content-between"><span class="fw-bold">{{ $reward['name'] }}</span><span>{{ $reward['quantity'] }} × PENDING</span></li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="small text-muted mb-0">No active promotion applies to this receipt.</p>
                                    @endif
                                @elseif ($receipt->status === 'approved')
                                    @forelse ($receipt->earnedRewards as $reward)
                                        <div class="d-flex justify-content-between border-bottom py-2"><span class="fw-bold">{{ $reward->premiumProduct->name ?? 'Premium product' }}</span><span>{{ $reward->reward_quantity }} × {{ strtoupper($reward->claim_status) }}</span></div>
                                    @empty
                                        <p class="small text-muted mb-0">No rewards were earned from this receipt.</p>
                                    @endforelse
                                @else
                                    <p class="small text-danger mb-0">Rejected receipts do not earn rewards.</p>
                                @endif
                            </div>
                            <div class="modal-footer border-top-0 justify-content-end">
                                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button>
                            </div>
                        </div>
                    </div>
                </div>

                @endpush

                @empty
                <tr>
                    <td colspan="2" class="text-center py-4 text-muted fw-bold">NO PENDING RECEIPTS IN QUEUE</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@stack('modals')
@endsection
