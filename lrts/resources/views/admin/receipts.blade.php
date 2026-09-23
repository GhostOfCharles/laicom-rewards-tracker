@extends('layouts.admin')

@section('content')
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <label class="fw-bold small me-2 mb-0">SORT BY:</label>
        <select class="form-select form-select-sm border-dark rounded-0 w-auto fw-bold">
            <option>PROCESSING</option>
            <option>APPROVED</option>
        </select>
    </div>
    
    <div class="border border-dark border-2 p-2">
        @if(session('success'))
            <div class="alert alert-success py-2 fw-bold small rounded-0">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger py-2 fw-bold small rounded-0">{{ $errors->first() }}</div>
        @endif

        <table class="table table-borderless mb-0">
            <tbody>
                @forelse($receipts as $receipt)
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
                        <div class="badge bg-warning text-dark border border-dark rounded-0 w-100 py-2 mb-2">STATUS: {{ strtoupper($receipt->status) }}</div>
                        @if($receipt->status === 'pending')
                            <form action="{{ route('admin.receipts.approve', $receipt->id) }}" method="POST" class="mb-1">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success rounded-0 w-100 fw-bold">APPROVE</button>
                            </form>
                            <form action="{{ route('admin.receipts.reject', $receipt->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger rounded-0 w-100 fw-bold">REJECT</button>
                            </form>
                        @endif
                    </td>
                </tr>

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

                <!-- Dynamic View Claimable Rewards Modal (Placeholder for next step) -->
                <div class="modal fade" id="viewRewardsModal{{ $receipt->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content border-dark border-2 rounded-0">
                            <div class="modal-header border-bottom border-dark">
                                <h6 class="modal-title fw-bold">CLAIMABLE REWARDS | ORDER #: {{ $receipt->salesman_order_number }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-start">
                                <p class="small text-muted">Reward calculation logic will be injected here next.</p>
                            </div>
                            <div class="modal-footer border-top-0 justify-content-end">
                                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button>
                            </div>
                        </div>
                    </div>
                </div>

                @empty
                <tr>
                    <td colspan="2" class="text-center py-4 text-muted fw-bold">NO PENDING RECEIPTS IN QUEUE</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection