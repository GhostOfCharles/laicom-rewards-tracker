@extends('layouts.admin')

@section('content')
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
                <tr>
                    <td class="text-start">SURF POWDER 20G</td>
                    <td>267</td>
                    <td class="text-success fw-bold">HIGH</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPremiumProductModal"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">BLUE DIPPER</td>
                    <td>20</td>
                    <td class="text-danger fw-bold">LOW</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPremiumProductModal"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">BREEZE 90G</td>
                    <td>70</td>
                    <td class="text-muted fw-bold">-</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPremiumProductModal"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
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
                    <th>BUY AMOUNT</th>
                    <th>GET AMOUNT</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start">BUY 6 BREEZE 200G GET 3 FREE BLUE DIPPER</td>
                    <td>6</td>
                    <td>3</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPromotionModal"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">BUY 3 SUNSILK SHAMPOO SMOOTH GET 1</td>
                    <td>3</td>
                    <td>1</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPromotionModal"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">BUY KNORR CUBES 10G</td>
                    <td>4</td>
                    <td>1</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPromotionModal"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- MODALS                                        -->
<!-- ============================================= -->

<!-- Add New Premium Product Modal (Wireframe 12) -->
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
                        <!-- Auto-generating an item code silently since it's required by our DB schema -->
                        <input type="hidden" name="item_code" value="{{ 'PRM-' . strtoupper(Str::random(5)) }}">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small">INITIAL STOCK AMOUNT:</label>
                        <input type="number" name="initial_stock" class="form-control border-dark rounded-0" required>
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

<!-- Edit Premium Product Modal (Wireframe 13) -->
<div class="modal fade" id="editPremiumProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">EDIT PREMIUM PRODUCT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT NAME:</label>
                        <input type="text" class="form-control border-dark rounded-0" value="SURF POWDER 20G">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CURRENT STOCK QUANTITY:</label>
                        <input type="number" class="form-control border-dark rounded-0 d-inline-block w-50" value="267">
                        <span class="ms-2 small fw-bold">CALCULATED STATUS: <span class="text-success">[ HIGH ]</span></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" class="form-control border-dark rounded-0">
                        <div class="small text-muted mt-1">CURRENT FILE: SURF_POWDER_20G.PNG</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 justify-content-end">
                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                <button type="button" class="btn btn-dark rounded-0">SAVE</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Active Promotion Modal (Wireframe 14) -->
<div class="modal fade" id="editPromotionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">EDIT ACTIVE PROMOTION</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PROMOTION NAME (OPTIONAL / AUTO-GENERATED):</label>
                        <input type="text" class="form-control border-dark rounded-0" value="BUY 6 BREEZE 200G GET 3 FREE BLUE DIPPER">
                    </div>
                    
                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-BUY CONDITION-</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">SELECT UNILEVER PRODUCT (FROM INVENTORY POOL):</label>
                        <select class="form-select border-dark rounded-0">
                            <option>BREEZE 200G</option>
                            <option>DOVE BEAUTY BAR SOAP WHITE 135G</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">REQUIRED QUANTITY TO BUY:</label>
                        <input type="number" class="form-control border-dark rounded-0" value="6">
                    </div>

                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-GET REWARD-</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">SELECTED FREE PRODUCT:</label>
                        <select class="form-select border-dark rounded-0">
                            <option>BLUE DIPPER</option>
                            <option>SURF POWDER 20G</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">FREE REWARD QUANTITY:</label>
                        <input type="number" class="form-control border-dark rounded-0" value="3">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 justify-content-end">
                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                <button type="button" class="btn btn-dark rounded-0">UPDATE PROMO</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Active Promotion Modal -->
<div class="modal fade" id="addPromotionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">ADD ACTIVE PROMOTION</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PROMOTION NAME (OPTIONAL / AUTO-GENERATED):</label>
                        <input type="text" class="form-control border-dark rounded-0">
                    </div>
                    
                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-BUY CONDITION-</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">SELECT UNILEVER PRODUCT (FROM INVENTORY POOL):</label>
                        <select class="form-select border-dark rounded-0">
                            <option selected disabled>Choose a product...</option>
                            <option>BREEZE 200G</option>
                            <option>DOVE BEAUTY BAR SOAP WHITE 135G</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">REQUIRED QUANTITY TO BUY:</label>
                        <input type="number" class="form-control border-dark rounded-0" placeholder="e.g., 6">
                    </div>

                    <h6 class="fw-bold mt-4 mb-2 border-bottom border-dark pb-1">-GET REWARD-</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">SELECTED FREE PRODUCT:</label>
                        <select class="form-select border-dark rounded-0">
                            <option selected disabled>Choose a premium reward...</option>
                            <option>BLUE DIPPER</option>
                            <option>SURF POWDER 20G</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">FREE REWARD QUANTITY:</label>
                        <input type="number" class="form-control border-dark rounded-0" placeholder="e.g., 1">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 justify-content-end">
                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                <button type="button" class="btn btn-dark rounded-0">SAVE PROMO</button>
            </div>
        </div>
    </div>
</div>
@endsection