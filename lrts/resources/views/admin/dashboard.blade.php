@extends('layouts.admin')

@section('content')

@if(session('success'))
    <div class="alert alert-success py-2 small fw-bold rounded-0 mb-4">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger py-2 small fw-bold rounded-0 mb-4">{{ $errors->first() }}</div>
@endif

<!-- Premium Products Section -->
<div class="mb-5">
    <div class="mb-2">
        <h6 class="mb-0 fw-bold">PREMIUM PRODUCTS @if($premiumExpiryAlertCount > 0)<span class="badge bg-danger rounded-0 align-middle ms-1">{{ $premiumExpiryAlertCount }}</span>@endif</h6>
        <span class="small text-muted">Promotional reward stock</span>
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

    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm laicom-btn-primary" data-bs-toggle="modal" data-bs-target="#addPremiumProductModal" title="Add New Premium Product" aria-label="Add premium product"><i class="bi bi-plus-lg"></i></button>
    </div>
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
        <h6 class="mb-0 fw-bold">ACTIVE PROMOTIONS</h6>
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
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm laicom-btn-primary" data-bs-toggle="modal" data-bs-target="#addPromotionModal" title="Add Active Promotion" aria-label="Add promotion"><i class="bi bi-plus-lg"></i></button>
    </div>
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
                        <label class="form-label fw-bold small">SELECT UNILEVER PRODUCT:</label>
                        <select name="buy_product_name" class="form-select border-dark rounded-0" required>
                            <option selected disabled value="">Choose a product...</option>
                            @foreach($inventory as $item)
                                <option value="{{ $item->name }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">REQUIRED QUANTITY TO BUY:</label>
                        <input type="number" name="required_quantity" class="form-control border-dark rounded-0" required min="1" placeholder="e.g., 6">
                    </div>

                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-GET REWARD-</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">SELECTED FREE PRODUCT:</label>
                        <select name="premium_product_id" class="form-select border-dark rounded-0" required>
                            <option selected disabled value="">Choose a premium reward...</option>
                            @foreach($allPremiumProducts as $premium)
                                <option value="{{ $premium->id }}">{{ \Illuminate\Support\Str::title($premium->name) }}</option>
                            @endforeach
                        </select>
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
