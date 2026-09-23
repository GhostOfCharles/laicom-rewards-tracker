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
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-bold">PREMIUM PRODUCTS</h6>
        <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#addPremiumProductModal" title="Add New Premium Product"><i class="bi bi-plus-lg"></i></button>
    </div>

    <div class="table-responsive border border-dark border-2 p-1">
        <table class="table table-bordered mb-0 text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>PRODUCT NAME</th>
                    <th>STOCK</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($premiumProducts as $premium)
                <tr>
                    <td class="text-start fw-bold">{{ $premium->name }}</td>
                    <td>{{ $premium->stock }}</td>
                    <td>
                        @if($premium->stock > 50)
                            <span class="text-success fw-bold">HIGH</span>
                        @elseif($premium->stock > 10)
                            <span class="text-warning fw-bold">MODERATE</span>
                        @else
                            <span class="text-danger fw-bold">LOW</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPremiumProductModal{{ $premium->id }}"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-3 text-muted fw-bold">NO PREMIUM PRODUCTS FOUND</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Active Promotions Section -->
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-bold">ACTIVE PROMOTIONS</h6>
        <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#addPromotionModal" title="Add Active Promotion"><i class="bi bi-plus-lg"></i></button>
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
                @forelse($promotions as $promo)
                <tr>
                    <td class="text-start fw-bold">{{ $promo->title }}</td>
                    <td>{{ $promo->required_quantity }}X {{ $promo->buy_product_name }}</td>
                    <td>{{ $promo->reward_quantity }}X {{ $promo->premiumProduct->name ?? 'Unknown' }}</td>
                    <td>
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPromotionModal{{ $promo->id }}"><i class="bi bi-pencil-square"></i></button>
                            <form action="{{ route('promotions.destroy', $promo->id) }}" method="POST" onsubmit="return confirm('Delete this promotion?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-3 text-muted fw-bold">NO ACTIVE PROMOTIONS FOUND</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- MODALS                                        -->
<!-- ============================================= -->

<!-- Add New Premium Product Modal -->
<div class="modal fade" id="addPremiumProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
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
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" name="image" class="form-control border-dark rounded-0" accept="image/*">
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
@foreach($premiumProducts as $premium)
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
                        <input type="text" name="name" class="form-control border-dark rounded-0" value="{{ $premium->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CURRENT STOCK QUANTITY:</label>
                        <input type="number" name="stock" class="form-control border-dark rounded-0" value="{{ $premium->stock }}" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" name="image" class="form-control border-dark rounded-0" accept="image/*">
                        @if($premium->image_path)
                            <div class="small text-muted mt-1">CURRENT: {{ basename($premium->image_path) }}</div>
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
        <div class="modal-content border-dark border-2 rounded-0">
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
                            @foreach($premiumProducts as $premium)
                                <option value="{{ $premium->id }}">{{ $premium->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">FREE REWARD QUANTITY:</label>
                        <input type="number" name="reward_quantity" class="form-control border-dark rounded-0" required min="1" placeholder="e.g., 3">
                    </div>

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
        <div class="modal-content border-dark border-2 rounded-0">
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
                            @foreach($premiumProducts as $premium)
                                <option value="{{ $premium->id }}" {{ $promo->premium_product_id == $premium->id ? 'selected' : '' }}>
                                    {{ $premium->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">FREE REWARD QUANTITY:</label>
                        <input type="number" name="reward_quantity" class="form-control border-dark rounded-0" value="{{ $promo->reward_quantity }}" required min="1">
                    </div>

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

@endsection