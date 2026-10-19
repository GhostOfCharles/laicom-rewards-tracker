                <!-- TAB 2: MY ORDER TRACKER -->
                <div class="tab-pane fade" id="tracker" role="tabpanel">
                    <div class="card rts-card"><div class="card-header">SUBMITTED ORDERS</div><div class="card-body p-0"><div class="table-responsive lrts-receipt-tracker-scroll">
                        <table class="table table-hover mb-0 align-middle text-center"><thead class="border-bottom border-dark"><tr><th class="py-3">ORDER NO.</th><th class="py-3">ORDER DATE</th><th class="py-3">STATUS</th><th class="py-3">REWARDS</th><th class="py-3">ACTIONS</th></tr></thead><tbody data-refresh-region="tracker">
                        @forelse ($receipts as $receipt)
                            @php $rewardStatus = $receipt->earnedRewards->contains(fn($reward) => $reward->claim_status === 'released') ? 'CONFIRM RECEIPT' : ($receipt->earnedRewards->contains(fn($reward) => $reward->claim_status === 'claim_requested') ? 'CLAIM REQUESTED' : ($receipt->earnedRewards->contains(fn($reward) => $reward->claim_status === 'claimed') ? 'RECEIVED' : ($receipt->earnedRewards->contains(fn($reward) => $reward->claim_status === 'unclaimed') ? 'AVAILABLE' : '—'))); @endphp
                            <tr><td class="fw-bold">{{ $receipt->salesman_order_number }}</td><td class="small">{{ $receipt->order_date?->format('M d, Y') ?? $receipt->submitted_at?->format('M d, Y') ?? '—' }}</td><td>
                                <span data-receipt-status="{{ $receipt->id }}" class="badge border border-dark w-75 py-2 {{ $receipt->status === 'approved' ? 'bg-success' : ($receipt->status === 'pending' ? 'bg-warning text-dark' : ($receipt->status === 'cancelled' ? 'bg-secondary' : 'bg-danger')) }}">{{ $receipt->status === 'pending' ? 'PROCESSING' : strtoupper($receipt->status) }}</span>
                            </td><td><span class="small fw-bold">{{ $rewardStatus }}</span></td><td><div class="d-flex justify-content-center gap-1 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#viewOrderModal{{ $receipt->id }}">ORDER DETAILS</button>
                                @if(in_array($receipt->status,['rejected','cancelled']))<button type="button" class="btn btn-sm btn-outline-primary" data-resubmit='@json($receipt->items->map(fn($item)=>["name"=>$item->product_name,"quantity"=>$item->quantity]))'>Resubmit</button>@endif
                                @if($receipt->status === 'pending')<form method="POST" action="{{ route('customer.receipts.cancel', $receipt) }}" onsubmit="return confirm('Cancel this processing order?');">@csrf<button type="submit" class="btn btn-sm btn-outline-danger fw-bold">CANCEL</button></form>@endif
                            </div></td></tr>
                        @empty<tr><td colspan="5" class="py-4 text-muted fw-bold">NO ORDERS SUBMITTED YET</td></tr>@endforelse
                        </tbody></table>
                    </div></div></div>
                {{ $receipts->appends(['tab'=>'tracker'])->links() }}
                </div>

