@extends('layouts.admin')

@section('content')

<!-- Premium Products Section -->
<div class="mb-5">
    <div class="mb-2">
        <div class="catalog-section-title">
            <h6 class="mb-0 fw-bold">PREMIUM PRODUCTS @if($premiumExpiryAlertCount > 0)<span class="badge bg-warning text-dark rounded-0 align-middle ms-1" title="Products that are expired or will expire within 30 days" data-admin-count="premium-expiry">{{ $premiumExpiryAlertCount }} EXPIRY ALERTS</span>@else<span class="badge bg-warning text-dark rounded-0 align-middle ms-1" title="Products that are expired or will expire within 30 days" data-admin-count="premium-expiry" hidden>0 EXPIRY ALERTS</span>@endif</h6>
            <button type="button" class="btn btn-sm laicom-btn-primary catalog-section-add" data-bs-toggle="modal" data-bs-target="#addPremiumProductModal" title="Add New Premium Product" aria-label="Add premium product"><i class="bi bi-plus-lg"></i></button>
        </div>
        <span class="small text-muted">Promotional reward stock · expiry alerts include expired products and products expiring within 30 days</span>
    </div>

    <form method="GET" action="{{ route('admin.dashboard') }}" class="catalog-filters mb-2">
        <input type="hidden" name="promotion_search" value="{{ $promotionSearch }}">
        <input type="hidden" name="promotion_status" value="{{ $promotionStatus }}">
        <div class="catalog-filter-search">
            <label for="premium_search" class="small fw-bold">SEARCH PRODUCTS</label>
            <input id="premium_search" name="premium_search" type="search" value="{{ $premiumSearch }}" class="form-control form-control-sm border-dark rounded-0" placeholder="Name or item code">
        </div>
        <div class="catalog-filter">
            <label for="premium_category" class="small fw-bold">FILTER BY CATEGORY</label>
            <select id="premium_category" name="premium_category" class="form-select form-select-sm border-dark rounded-0">
                <option value="">All</option>
                @foreach (\App\Models\PremiumProduct::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" {{ $premiumCategory === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="catalog-filter">
            <label for="premium_stock" class="small fw-bold">FILTER BY STOCK</label>
            <select id="premium_stock" name="premium_stock" class="form-select form-select-sm border-dark rounded-0">
                <option value="all" {{ $premiumStock === 'all' ? 'selected' : '' }}>All</option>
                <option value="high" {{ $premiumStock === 'high' ? 'selected' : '' }}>High</option>
                <option value="mid" {{ $premiumStock === 'mid' ? 'selected' : '' }}>Mid</option>
                <option value="low" {{ $premiumStock === 'low' ? 'selected' : '' }}>Low</option>
                <option value="out" {{ $premiumStock === 'out' ? 'selected' : '' }}>Out of Stock</option>
            </select>
        </div>
        <div class="catalog-filter">
            <label for="premium_availability" class="small fw-bold">FILTER BY AVAILABILITY</label>
            <select id="premium_availability" name="premium_availability" class="form-select form-select-sm border-dark rounded-0">
                <option value="all" {{ $premiumAvailability === 'all' ? 'selected' : '' }}>All</option>
                <option value="available" {{ $premiumAvailability === 'available' ? 'selected' : '' }}>Available</option>
                <option value="out" {{ $premiumAvailability === 'out' ? 'selected' : '' }}>Out of Stock</option>
            </select>
        </div>
        <div class="catalog-filter">
            <label for="premium_expiry" class="small fw-bold">FILTER BY EXPIRY</label>
            <select id="premium_expiry" name="premium_expiry" class="form-select form-select-sm border-dark rounded-0">
                <option value="" {{ $premiumExpiry === '' ? 'selected' : '' }}>All</option>
                <option value="perishable" {{ $premiumExpiry === 'perishable' ? 'selected' : '' }}>Perishable</option>
                <option value="expiring_soon" {{ $premiumExpiry === 'expiring_soon' ? 'selected' : '' }}>Expiring soon</option>
                <option value="expired" {{ $premiumExpiry === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm laicom-btn-primary catalog-filter-submit">SEARCH</button>
    </form>

    <div class="table-responsive laicom-card p-1">
        <table class="table table-bordered laicom-table mb-0 text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">PRODUCT NAME</th>
                    <th scope="col">CATEGORY</th>
                    <th scope="col">STOCK</th>
                    <th scope="col">STATUS</th>
                    <th scope="col">EXPIRY</th>
                    <th scope="col">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($visiblePremiumProducts as $premium)
                    @include('admin.partials.premium-product-row', ['premium' => $premium])
                @empty
                <tr>
                    <td colspan="6" class="text-center py-3 text-muted fw-bold">NO PREMIUM PRODUCTS FOUND</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="catalog-table-footer">
        <span class="small text-muted">Showing {{ min(5, $premiumProductCount) }} of {{ $premiumProductCount }} products</span>
        @if($premiumProductCount > 5)
            <button type="button" class="btn btn-sm btn-outline-dark rounded-0 fw-bold" data-bs-toggle="modal" data-bs-target="#allPremiumProductsModal">SHOW MORE</button>
        @endif
    </div>
</div>

<!-- Active Promotions Section -->
<div>
    <div class="mb-2">
        <div class="catalog-section-title">
            <h6 class="mb-0 fw-bold">ACTIVE PROMOTIONS</h6>
            <button type="button" class="btn btn-sm laicom-btn-primary catalog-section-add" data-bs-toggle="modal" data-bs-target="#addPromotionModal" title="Add Active Promotion" aria-label="Add promotion"><i class="bi bi-plus-lg"></i></button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.dashboard') }}" class="promotion-filters mb-2">
        <input type="hidden" name="premium_search" value="{{ $premiumSearch }}">
        <input type="hidden" name="premium_category" value="{{ $premiumCategory }}">
        <input type="hidden" name="premium_stock" value="{{ $premiumStock }}">
        <input type="hidden" name="premium_availability" value="{{ $premiumAvailability }}">
        <input type="hidden" name="premium_expiry" value="{{ $premiumExpiry }}">
        <div class="promotion-filter-search">
            <label for="promotion_search" class="small fw-bold">SEARCH PROMOTIONS</label>
            <input id="promotion_search" name="promotion_search" type="search" value="{{ $promotionSearch }}" class="form-control form-control-sm border-dark rounded-0" placeholder="Title or product name">
        </div>
        <div class="catalog-filter">
            <label for="promotion_status" class="small fw-bold">FILTER BY STATUS</label>
            <select id="promotion_status" name="promotion_status" class="form-select form-select-sm border-dark rounded-0">
                <option value="all" {{ $promotionStatus === 'all' ? 'selected' : '' }}>All</option>
                <option value="active" {{ $promotionStatus === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $promotionStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm laicom-btn-primary catalog-filter-submit">SEARCH</button>
    </form>
    <div class="table-responsive border border-dark border-2 p-1">
        <table class="table table-bordered mb-0 text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>PROMOTION NAME</th>
                    <th>BUY REQUIREMENT</th>
                    <th>GET REWARD</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($visiblePromotions as $promo)
                    @include('admin.partials.promotion-row', ['promo' => $promo])
                @empty
                <tr>
                    <td colspan="4" class="text-center py-3 text-muted fw-bold">NO ACTIVE PROMOTIONS FOUND</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="catalog-table-footer">
        <span class="small text-muted">Showing {{ min(5, $promotionCount) }} of {{ $promotionCount }} promotions</span>
        @if($promotionCount > 5)
            <button type="button" class="btn btn-sm btn-outline-dark rounded-0 fw-bold" data-bs-toggle="modal" data-bs-target="#allPromotionsModal">SHOW MORE</button>
        @endif
    </div>
</div>

<!-- ============================================= -->
<!-- MODALS                                        -->
<!-- ============================================= -->

@if($premiumProductCount > 5)
<div class="modal fade" id="allPremiumProductsModal" tabindex="-1" aria-labelledby="allPremiumProductsTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content laicom-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="allPremiumProductsTitle">ALL PREMIUM PRODUCTS <span class="small fw-normal">({{ $premiumProductCount }})</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table class="table table-bordered laicom-table mb-0 text-center align-middle">
                        <thead><tr><th>PRODUCT NAME</th><th>CATEGORY</th><th>STOCK</th><th>STATUS</th><th>EXPIRY</th><th>ACTIONS</th></tr></thead>
                        <tbody>@foreach($premiumProducts as $premium) @include('admin.partials.premium-product-row', ['premium' => $premium]) @endforeach</tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2"><span class="small text-muted me-auto">Showing all {{ $premiumProductCount }} matching products</span><button type="button" class="btn btn-sm btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button></div>
        </div>
    </div>
</div>
@endif

@if($promotionCount > 5)
<div class="modal fade" id="allPromotionsModal" tabindex="-1" aria-labelledby="allPromotionsTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content laicom-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="allPromotionsTitle">ALL PROMOTIONS <span class="small fw-normal">({{ $promotionCount }})</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table class="table table-bordered laicom-table mb-0 text-center align-middle">
                        <thead><tr><th>PROMOTION NAME</th><th>BUY REQUIREMENT</th><th>GET REWARD</th><th>ACTIONS</th></tr></thead>
                        <tbody>@foreach($promotions as $promo) @include('admin.partials.promotion-row', ['promo' => $promo]) @endforeach</tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2"><span class="small text-muted me-auto">Showing all {{ $promotionCount }} matching promotions</span><button type="button" class="btn btn-sm btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button></div>
        </div>
    </div>
</div>
@endif

<!-- Add New Premium Product Modal -->
<div class="modal fade" id="addPremiumProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content laicom-modal">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">ADD NEW PREMIUM PRODUCT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small">PRODUCT NAME:</label>
                        <input type="text" name="name" class="form-control border-dark rounded-0" required>
                        <input type="hidden" name="item_code" value="{{ 'PRM-' . strtoupper(Str::random(5)) }}">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small">INITIAL STOCK AMOUNT:</label>
                        <input type="number" name="initial_stock" class="form-control border-dark rounded-0" required min="0">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small">CATEGORY:</label>
                        <select name="category" class="form-select border-dark border-2 rounded-0" required>
                            <option value="" selected disabled>Select a category</option>
                            @foreach (\App\Models\PremiumProduct::CATEGORIES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2 text-start">
                        <input type="hidden" name="is_perishable" value="0">
                        <div class="form-check">
                            <input type="checkbox" name="is_perishable" value="1" class="form-check-input premium-perishable-toggle" id="addPremiumPerishable">
                            <label class="form-check-label fw-bold small" for="addPremiumPerishable">This is a perishable food item</label>
                        </div>
                        <div class="small text-muted">Enable expiry tracking for food or consumable rewards.</div>
                    </div>
                    <div class="premium-expiry-fields d-none" id="addPremiumExpiryFields">
                        <div class="mb-3 text-start">
                            <label class="form-label fw-bold small">ENTRY DATE:</label>
                            <input type="date" name="entry_date" class="form-control border-dark rounded-0">
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label fw-bold small">EXPIRY DATE:</label>
                            <input type="date" name="expiry_date" class="form-control border-dark rounded-0">
                        </div>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" name="image" class="form-control border-dark rounded-0" accept="image/png,image/jpeg,image/gif">
                    </div>
                    <div class="modal-footer border-top-0 justify-content-end px-0 pb-0">
                        <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn btn-dark rounded-0">SAVE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Edit Premium Product Modals (NEW — was missing) -->
@foreach($allPremiumProducts as $premium)
<div class="modal fade" id="editPremiumProductModal{{ $premium->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">EDIT PREMIUM PRODUCT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form action="{{ route('products.update', $premium->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT NAME:</label>
                        <input type="text" name="name" class="form-control border-dark rounded-0" value="{{ \Illuminate\Support\Str::title($premium->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CURRENT STOCK QUANTITY:</label>
                        <input type="number" name="stock" class="form-control border-dark rounded-0" value="{{ $premium->stock }}" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CATEGORY:</label>
                        <select name="category" class="form-select border-dark border-2 rounded-0" required>
                            @if (! $premium->category || ! array_key_exists($premium->category, \App\Models\PremiumProduct::CATEGORIES))
                                <option value="" selected disabled>Select a category for this legacy item</option>
                            @endif
                            @foreach (\App\Models\PremiumProduct::CATEGORIES as $value => $label)
                                <option value="{{ $value }}" {{ $premium->category === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="hidden" name="is_perishable" value="0">
                        <div class="form-check">
                            <input type="checkbox" name="is_perishable" value="1" class="form-check-input premium-perishable-toggle" id="editPremiumPerishable{{ $premium->id }}" {{ $premium->is_perishable ? 'checked' : '' }} aria-controls="editPremiumExpiryFields{{ $premium->id }}">
                            <label class="form-check-label fw-bold small" for="editPremiumPerishable{{ $premium->id }}">This is a perishable food item</label>
                        </div>
                        <div class="small text-muted">Enable expiry tracking for food or consumable rewards.</div>
                    </div>
                    <div class="premium-expiry-fields {{ $premium->is_perishable ? '' : 'd-none' }}" id="editPremiumExpiryFields{{ $premium->id }}">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">ENTRY DATE:</label>
                            <input type="date" name="entry_date" class="form-control border-dark rounded-0" value="{{ $premium->entry_date?->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">EXPIRY DATE:</label>
                            <input type="date" name="expiry_date" class="form-control border-dark rounded-0" value="{{ $premium->expiry_date?->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" name="image" class="form-control border-dark rounded-0" accept="image/png,image/jpeg,image/gif">
                        @if ($premium->image_path)
                            <div class="small text-muted mt-2 d-flex align-items-center gap-2"><img src="{{ asset('storage/' . $premium->image_path) }}" alt="Current {{ $premium->name }} image" class="laicom-thumbnail">Current image</div>
                        @endif
                    </div>

                    <div class="modal-footer border-top-0 justify-content-end px-0 pb-0">
                        <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn btn-dark rounded-0">SAVE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- Add Active Promotion Modal -->
<div class="modal fade" id="addPromotionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content laicom-modal">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">ADD ACTIVE PROMOTION</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form action="{{ route('promotions.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PROMOTION NAME (OPTIONAL):</label>
                        <input type="text" name="title" class="form-control border-dark rounded-0" placeholder="Auto-generated if left blank">
                    </div>

                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-BUY CONDITION-</h6>
                    <div class="mb-3">
                        <label for="addPromotionBuyProduct" class="form-label fw-bold small">SELECT UNILEVER PRODUCT:</label>
                        <div class="lrts-admin-product-picker" data-admin-product-picker>
                            <input type="text" id="addPromotionBuyProduct" class="form-select border-dark rounded-0 lrts-admin-product-picker-input" placeholder="Choose a product..." aria-label="Select Unilever product" role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-expanded="false" aria-controls="addPromotionBuyProductOptions" autocomplete="off" required>
                            <input type="hidden" name="buy_product_name" data-picker-value>
                            <button type="button" class="lrts-admin-product-picker-toggle" aria-label="Toggle Unilever product list" aria-controls="addPromotionBuyProductOptions" aria-expanded="false"><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                            <div id="addPromotionBuyProductOptions" class="lrts-admin-product-picker-options" role="listbox" aria-label="Unilever products" hidden>
                                @foreach($inventory as $item)
                                    <button type="button" id="add-promotion-buy-option-{{ $loop->index }}" class="lrts-admin-product-picker-option" role="option" aria-selected="false" data-product-label="{{ $item->name }}" data-product-value="{{ $item->name }}">
                                        <img src="{{ $item->image_path ? asset('storage/' . $item->image_path) : asset('images/laicom-logo.png') }}" alt="" loading="lazy" class="{{ $item->image_path ? '' : 'is-placeholder' }}" onerror="this.onerror=null; this.src='{{ asset('images/laicom-logo.png') }}'; this.classList.add('is-placeholder');">
                                        <span>{{ $item->name }}</span>
                                    </button>
                                @endforeach
                                <div class="lrts-admin-product-picker-empty" hidden>No matching products found.</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">REQUIRED QUANTITY TO BUY:</label>
                        <input type="number" name="required_quantity" class="form-control border-dark rounded-0" required min="1" placeholder="e.g., 6">
                    </div>

                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-GET REWARD-</h6>
                    <div class="mb-3">
                        <label for="addPromotionPremiumProduct" class="form-label fw-bold small">SELECTED FREE PRODUCT:</label>
                        <div class="lrts-admin-product-picker" data-admin-product-picker>
                            <input type="text" id="addPromotionPremiumProduct" class="form-select border-dark rounded-0 lrts-admin-product-picker-input" placeholder="Choose a premium reward..." aria-label="Select premium reward" role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-expanded="false" aria-controls="addPromotionPremiumProductOptions" autocomplete="off" required>
                            <input type="hidden" name="premium_product_id" data-picker-value>
                            <button type="button" class="lrts-admin-product-picker-toggle" aria-label="Toggle premium reward list" aria-controls="addPromotionPremiumProductOptions" aria-expanded="false"><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                            <div id="addPromotionPremiumProductOptions" class="lrts-admin-product-picker-options" role="listbox" aria-label="Premium rewards" hidden>
                                @foreach($allPremiumProducts as $premium)
                                    <button type="button" id="add-promotion-premium-option-{{ $loop->index }}" class="lrts-admin-product-picker-option" role="option" aria-selected="false" data-product-label="{{ \Illuminate\Support\Str::title($premium->name) }}" data-product-value="{{ $premium->id }}">
                                        <img src="{{ $premium->image_path ? asset('storage/' . $premium->image_path) : asset('images/laicom-logo.png') }}" alt="" loading="lazy" class="{{ $premium->image_path ? '' : 'is-placeholder' }}" onerror="this.onerror=null; this.src='{{ asset('images/laicom-logo.png') }}'; this.classList.add('is-placeholder');">
                                        <span>{{ \Illuminate\Support\Str::title($premium->name) }}</span>
                                    </button>
                                @endforeach
                                <div class="lrts-admin-product-picker-empty" hidden>No matching premium rewards found.</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">FREE REWARD QUANTITY:</label>
                        <input type="number" name="reward_quantity" class="form-control border-dark rounded-0" required min="1" placeholder="e.g., 3">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label fw-bold small">START DATE:</label><input type="date" name="start_date" class="form-control border-dark rounded-0" value="{{ today()->toDateString() }}" required></div>
                        <div class="col-6"><label class="form-label fw-bold small">END DATE:</label><input type="date" name="end_date" class="form-control border-dark rounded-0" value="{{ today()->addYear()->toDateString() }}" required></div>
                    </div>
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="newPromotionActive" checked><label class="form-check-label fw-bold small" for="newPromotionActive">ACTIVE</label></div>

                    <div class="modal-footer border-top-0 justify-content-end px-0 pb-0">
                        <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn btn-dark rounded-0">SAVE PROMO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Edit Promotion Modals -->
@foreach($promotions as $promo)
<div class="modal fade" id="editPromotionModal{{ $promo->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content laicom-modal">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">EDIT ACTIVE PROMOTION</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form action="{{ route('promotions.update', $promo->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-bold small">PROMOTION NAME:</label>
                        <input type="text" name="title" class="form-control border-dark rounded-0" value="{{ $promo->title }}">
                    </div>

                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-BUY CONDITION-</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">SELECT UNILEVER PRODUCT:</label>
                        <select name="buy_product_name" class="form-select border-dark rounded-0" required>
                            @foreach($inventory as $item)
                                <option value="{{ $item->name }}" {{ $promo->buy_product_name === $item->name ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">REQUIRED QUANTITY TO BUY:</label>
                        <input type="number" name="required_quantity" class="form-control border-dark rounded-0" value="{{ $promo->required_quantity }}" required min="1">
                    </div>

                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-GET REWARD-</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">SELECTED FREE PRODUCT:</label>
                        <select name="premium_product_id" class="form-select border-dark rounded-0" required>
                            @foreach($allPremiumProducts as $premium)
                                <option value="{{ $premium->id }}" {{ $promo->premium_product_id == $premium->id ? 'selected' : '' }}>
                                    {{ \Illuminate\Support\Str::title($premium->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">FREE REWARD QUANTITY:</label>
                        <input type="number" name="reward_quantity" class="form-control border-dark rounded-0" value="{{ $promo->reward_quantity }}" required min="1">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label fw-bold small">START DATE:</label><input type="date" name="start_date" class="form-control border-dark rounded-0" value="{{ $promo->start_date?->format('Y-m-d') }}" required></div>
                        <div class="col-6"><label class="form-label fw-bold small">END DATE:</label><input type="date" name="end_date" class="form-control border-dark rounded-0" value="{{ $promo->end_date?->format('Y-m-d') }}" required></div>
                    </div>
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="editPromotionActive{{ $promo->id }}" @checked($promo->is_active)><label class="form-check-label fw-bold small" for="editPromotionActive{{ $promo->id }}">ACTIVE</label></div>

                    <div class="modal-footer border-top-0 justify-content-end px-0 pb-0">
                        <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn btn-dark rounded-0">UPDATE PROMO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

<script>
    (() => {
        document.querySelectorAll('#addPromotionModal [data-admin-product-picker]').forEach((picker) => {
            const input = picker.querySelector('.lrts-admin-product-picker-input');
            const valueField = picker.querySelector('[data-picker-value]');
            const toggle = picker.querySelector('.lrts-admin-product-picker-toggle');
            const dropdown = picker.querySelector('.lrts-admin-product-picker-options');
            const optionItems = Array.from(dropdown.querySelectorAll('.lrts-admin-product-picker-option'));
            const emptyState = dropdown.querySelector('.lrts-admin-product-picker-empty');
            const modalBody = picker.closest('.modal-body');
            let selectedOption = null;
            let activeOption = null;

            function findExactOption(value) {
                const normalizedValue = value.trim().toLocaleLowerCase();
                return normalizedValue
                    ? optionItems.find((option) => option.dataset.productLabel.toLocaleLowerCase() === normalizedValue) ?? null
                    : null;
            }

            function syncValue() {
                selectedOption = findExactOption(input.value);
                valueField.value = selectedOption?.dataset.productValue ?? '';
                input.setCustomValidity(input.value && !selectedOption ? 'Choose a product from the list.' : '');
                optionItems.forEach((option) => option.setAttribute('aria-selected', option === selectedOption ? 'true' : 'false'));
            }

            function clearActiveOption() {
                if (activeOption) activeOption.classList.remove('is-active');
                activeOption = null;
                input.removeAttribute('aria-activedescendant');
            }

            function filterOptions(query = input.value.trim()) {
                const normalizedQuery = query.toLocaleLowerCase();
                let visibleCount = 0;

                optionItems.forEach((option) => {
                    const matches = option.dataset.productLabel.toLocaleLowerCase().includes(normalizedQuery);
                    option.hidden = !matches;
                    if (matches) visibleCount++;
                });

                emptyState.hidden = visibleCount > 0;
                clearActiveOption();
            }

            function positionDropdown() {
                if (dropdown.hidden) return;

                const controlRect = input.getBoundingClientRect();
                const modalRect = modalBody.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const limitTop = Math.max(0, modalRect.top + 8);
                const limitBottom = Math.min(viewportHeight, modalRect.bottom) - 8;
                const spaceBelow = Math.max(0, limitBottom - controlRect.bottom - 4);
                const spaceAbove = Math.max(0, controlRect.top - limitTop - 4);
                const maxHeight = Math.min(272, viewportHeight * 0.4);
                const opensAbove = spaceBelow < Math.min(maxHeight, 168) && spaceAbove > spaceBelow;
                const availableSpace = opensAbove ? spaceAbove : spaceBelow;

                dropdown.classList.toggle('is-open-above', opensAbove);
                dropdown.style.maxHeight = `${Math.max(1, Math.min(maxHeight, availableSpace))}px`;
            }

            function openDropdown(showAll = false) {
                dropdown.hidden = false;
                picker.classList.add('is-open');
                input.setAttribute('aria-expanded', 'true');
                toggle.setAttribute('aria-expanded', 'true');
                filterOptions(showAll ? '' : input.value.trim());
                positionDropdown();
            }

            function closeDropdown() {
                dropdown.hidden = true;
                picker.classList.remove('is-open');
                dropdown.classList.remove('is-open-above');
                dropdown.style.maxHeight = '';
                input.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-expanded', 'false');
                clearActiveOption();
                optionItems.forEach((option) => option.setAttribute('aria-selected', option === selectedOption ? 'true' : 'false'));
            }

            function chooseOption(option) {
                if (!option) return;
                selectedOption = option;
                input.value = option.dataset.productLabel;
                valueField.value = option.dataset.productValue;
                input.setCustomValidity('');
                optionItems.forEach((item) => item.setAttribute('aria-selected', item === option ? 'true' : 'false'));
                closeDropdown();
                input.focus({ preventScroll: true });
            }

            function setActiveOption(option) {
                clearActiveOption();
                activeOption = option;
                if (!option) return;
                activeOption.classList.add('is-active');
                optionItems.forEach((item) => item.setAttribute('aria-selected', item === option ? 'true' : 'false'));
                input.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({ block: 'nearest' });
            }

            input.addEventListener('click', () => {
                if (dropdown.hidden) {
                    openDropdown(true);
                    if (selectedOption) input.select();
                }
            });

            input.addEventListener('input', () => {
                syncValue();
                openDropdown();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (dropdown.hidden) openDropdown();
                    const visibleOptions = optionItems.filter((option) => !option.hidden);
                    if (!visibleOptions.length) return;
                    const currentIndex = visibleOptions.indexOf(activeOption);
                    const nextIndex = event.key === 'ArrowDown'
                        ? (currentIndex + 1) % visibleOptions.length
                        : (currentIndex <= 0 ? visibleOptions.length - 1 : currentIndex - 1);
                    setActiveOption(visibleOptions[nextIndex]);
                } else if (event.key === 'Enter') {
                    event.preventDefault();
                    const exactOption = findExactOption(input.value);
                    chooseOption(dropdown.hidden ? exactOption : activeOption || exactOption);
                } else if (event.key === 'Escape' && !dropdown.hidden) {
                    event.preventDefault();
                    closeDropdown();
                }
            });

            toggle.addEventListener('click', () => {
                if (dropdown.hidden) {
                    openDropdown(true);
                    if (selectedOption) input.select();
                } else {
                    closeDropdown();
                }
                input.focus({ preventScroll: true });
            });

            toggle.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !dropdown.hidden) {
                    event.preventDefault();
                    closeDropdown();
                    input.focus({ preventScroll: true });
                }
            });

            optionItems.forEach((option) => {
                option.addEventListener('mousedown', (event) => event.preventDefault());
                option.addEventListener('click', () => chooseOption(option));
            });

            picker.addEventListener('focusout', () => {
                window.setTimeout(() => {
                    if (!picker.contains(document.activeElement)) closeDropdown();
                }, 0);
            });

            document.addEventListener('pointerdown', (event) => {
                if (!picker.contains(event.target)) closeDropdown();
            });

            window.addEventListener('resize', positionDropdown);
            modalBody.addEventListener('scroll', positionDropdown, { passive: true });

            const form = picker.closest('form');
            form?.addEventListener('submit', () => {
                syncValue();
                if (selectedOption) input.value = selectedOption.dataset.productLabel;
            });

            syncValue();
        });
    })();
</script>

<script>
    (() => {
        document.querySelectorAll('.premium-perishable-toggle').forEach((toggle) => {
            const form = toggle.closest('form');
            const fields = form?.querySelector('.premium-expiry-fields');
            const updateFields = () => fields?.classList.toggle('d-none', !toggle.checked);
            toggle.addEventListener('change', updateFields);
            updateFields();
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('button[data-bs-toggle="modal"][data-bs-target]');
            const currentModal = trigger?.closest('.modal.show');
            if (!currentModal || !['allPremiumProductsModal', 'allPromotionsModal'].includes(currentModal.id)) return;

            const target = document.querySelector(trigger.getAttribute('data-bs-target'));
            if (!target) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            currentModal.addEventListener('hidden.bs.modal', () => bootstrap.Modal.getOrCreateInstance(target).show(), { once: true });
            bootstrap.Modal.getOrCreateInstance(currentModal).hide();
        }, true);
    })();
</script>
@endsection
