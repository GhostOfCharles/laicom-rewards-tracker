                <!-- TAB 3: PROMOS & CLAIMS -->
                <div class="tab-pane fade" id="promos" role="tabpanel">

                    <!-- Section Toggle -->
                    <div class="row g-0 lrts-section-toggle">
                        <div class="col-4">
                            <button type="button" class="lrts-section-btn is-active" data-promos-section="promotions">
                                ACTIVE PROMOTIONS
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="lrts-section-btn" data-promos-section="claims">
                                CLAIMABLE REWARDS
                            </button>
                        </div>
                        <div class="col-4"><button type="button" class="lrts-section-btn" data-promos-section="history">CLAIM HISTORY</button></div>
                    </div>

                    <!-- Section: Active Promotions -->
                    <div class="lrts-section-pane is-active" data-promos-pane="promotions">
                        <div class="card rts-card">
                            <div class="card-header">ACTIVE PROMOTIONS</div>
                            <div class="card-body">
                                <p class="small text-muted fw-bold mb-4">Purchase the required items to automatically earn these premium rewards!</p>

                                @if ($promotions->count() > 0)
                                    <div class="row g-4">
                                        @foreach ($promotions as $promo)
                                            @php
                                                $buyProduct = $products->firstWhere('name', $promo->buy_product_name);
                                            @endphp
                                            <div class="col-md-6">
                                                <div class="promo-card">

                                                    <span class="promo-badge">PROMO {{ $loop->iteration }}</span>

                                                    <div class="promo-headline">
                                                        BUY {{ $promo->required_quantity }} {{ $promo->buy_product_name }}
                                                        <span class="promo-get">GET {{ $promo->reward_quantity }} {{ $promo->premiumProduct->name ?? 'Premium Reward' }}</span>
                                                    </div>

                                                    <div class="promo-products">

                                                        <div class="promo-product">
                                                            <x-product-image :product="$buyProduct" :name="$promo->buy_product_name" :size="104" class="promo-product-img" />
                                                            <span class="promo-product-tag">BUY {{ $promo->required_quantity }}</span>
                                                            <p class="promo-product-name">{{ $promo->buy_product_name }}</p>
                                                        </div>

                                                        <div class="promo-arrow">
                                                            <svg viewBox="0 0 36 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                                <path d="M0 9h22V2.5L36 12l-14 9.5V15H0z"/>
                                                            </svg>
                                                        </div>

                                                        <div class="promo-product">
                                                            <x-product-image :product="$promo->premiumProduct" :size="104" class="promo-product-img" />
                                                            <span class="promo-product-tag">GET {{ $promo->reward_quantity }}</span>
                                                            <p class="promo-product-name">{{ $promo->premiumProduct->name ?? 'Premium Reward' }}</p>
                                                            @if ((int) ($promo->premiumProduct?->stock ?? 0) > 0)
                                                                <span class="promo-stock">
                                                                    <i class="bi bi-box-seam"></i>
                                                                    {{ $promo->premiumProduct->stock }} left
                                                                </span>
                                                            @else
                                                                <span class="promo-stock is-no-stock">No Stock</span>
                                                            @endif
                                                        </div>

                                                    </div>

                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="border border-dark p-4 text-center bg-light">
                                        <i class="bi bi-tags text-muted lrts-empty-state-icon"></i>
                                        <p class="mt-2 mb-0 fw-bold text-muted">NO ACTIVE PROMOTIONS AT THIS TIME</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Section: Claimable Rewards -->
                    <div class="lrts-section-pane" data-promos-pane="claims">
                        <div class="card rts-card">
                            <div class="card-header">MY CLAIMABLE REWARDS</div>
                            <div class="card-body">
                                <p class="small text-muted fw-bold mb-4">Request your approved rewards, show the claim code to staff, then confirm here after you have received the items. Confirmed rewards move to your claim history.</p>
                                <div class="lrts-claimable-list">
                                {{ $claimableReceipts->appends(['tab'=>'promos','section'=>'claims'])->links() }}
                                @if ($claimableReceipts->count() > 0)
                                    @foreach($claimableReceipts as $claimReceipt)
                                        @php $claimCode = $claimReceipt->earnedRewards->first(fn($reward) => $reward->claim_status === 'claim_requested')?->claim_code; $awaitingConfirmation = $claimReceipt->earnedRewards->contains(fn($reward) => $reward->claim_status === 'released'); @endphp
                                        <div class="border border-dark mb-3 p-3 bg-white"><div class="d-flex flex-wrap justify-content-between gap-2 align-items-center"><div><strong>ORDER #{{ $claimReceipt->salesman_order_number }}</strong><div class="small text-muted">{{ $claimReceipt->earnedRewards->whereIn('claim_status', ['unclaimed', 'claim_requested', 'released'])->sum('reward_quantity') }} rewards · approved {{ $claimReceipt->reviewed_at?->format('M d, Y') ?? '' }}</div></div>
                                            @if($claimCode)<div class="text-end"><span class="badge bg-warning text-dark border border-dark rounded-0">REQUESTED</span><div class="fw-bold fs-5">{{ $claimCode }}</div><small>Show this code to staff.</small></div>@endif
                                            @if($claimReceipt->earnedRewards->contains(fn($reward) => $reward->claim_status === 'unclaimed'))<form method="POST" action="{{ route('customer.receipts.claim', $claimReceipt) }}" onsubmit="return confirm('Request all available rewards for this order?');">@csrf<button class="btn rts-btn-primary btn-sm fw-bold" type="submit">{{ $claimCode ? 'REQUEST REMAINING REWARDS' : 'REQUEST CLAIM CODE' }}</button></form>@endif
                                        </div><div class="table-responsive mt-2"><table class="table table-sm mb-0 align-middle"><thead><tr><th>PREMIUM ITEM</th><th>QTY</th><th>STATUS</th></tr></thead><tbody>
                                            @foreach($claimReceipt->earnedRewards->whereIn('claim_status', ['unclaimed', 'claim_requested', 'released']) as $reward)<tr><td>{{ $reward->premiumProduct?->name ?? $reward->reward_product_name ?? 'Reward item' }}</td><td>{{ number_format($reward->reward_quantity) }}</td><td><x-status-badge :data-claim-status="$reward->id" :status="$reward->claim_status" />@if($reward->expires_at && in_array($reward->claim_status,['unclaimed','claim_requested']))<small class="d-block text-muted mt-1">Expires {{ $reward->expires_at->format('M d, Y') }} ({{ max(0,(int) now()->diffInDays($reward->expires_at,false)) }} days left)</small>@endif</td></tr>@endforeach
                                        </tbody></table></div>
                                        @if($awaitingConfirmation)<div class="alert alert-warning border-dark rounded-0 small fw-bold mt-2 mb-2">Staff marked the reward handover complete. Confirm only if you have received these items.</div><form method="POST" action="{{ route('customer.receipts.confirm-received', $claimReceipt) }}" onsubmit="return confirm('Confirm that you have received the handed-over rewards?');">@csrf<button class="btn rts-btn-primary btn-sm fw-bold" type="submit"><i class="bi bi-check-circle me-1"></i>I RECEIVED THESE REWARDS</button></form>@endif
                                        <button type="button" class="btn btn-sm btn-link px-0" data-open-reward-ticket data-receipt-id="{{ $claimReceipt->id }}">Report a problem with these rewards</button></div>
                                    @endforeach
                                @else
                                    <div class="border border-dark p-4 text-center bg-light">
                                        <i class="bi bi-box-seam text-muted lrts-empty-state-icon"></i>
                                        <p class="mt-2 mb-0 fw-bold text-muted">YOU HAVE NO REWARDS WAITING FOR A CLAIM REQUEST</p>
                                    </div>
                                @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lrts-section-pane" data-promos-pane="history"><div class="card rts-card"><div class="card-header">REWARD CLAIM HISTORY</div><div class="card-body p-0"><div class="table-responsive lrts-claim-history-scroll"><table class="table table-sm align-middle mb-0"><thead><tr><th>ORDER</th><th>REWARD</th><th>QTY</th><th>STATUS</th><th>UPDATED</th><th>DETAILS</th></tr></thead><tbody>
                        @forelse($claimHistory as $reward)<tr><td>{{ $reward->receipt?->salesman_order_number ?? '—' }}</td><td>{{ $reward->premiumProduct?->name ?? $reward->reward_product_name ?? 'Reward item' }}</td><td>{{ $reward->reward_quantity }}</td><td>{{ $reward->claim_status === 'claimed' ? 'RECEIVED' : strtoupper($reward->claim_status) }}</td><td>{{ ($reward->claimed_at ?? $reward->voided_at ?? $reward->expired_at ?? $reward->updated_at)?->format('M d, Y') ?? '—' }}</td><td>{{ $reward->void_reason ?? ($reward->releaser?->name ? 'Handed over by ' . $reward->releaser->name : '—') }}</td></tr>@empty<tr><td colspan="6" class="py-4 text-center text-muted">No completed claims yet.</td></tr>@endforelse
                    </tbody></table></div>{{ $claimHistory->appends(['tab'=>'promos','section'=>'history'])->links() }}</div></div></div>

                </div>

