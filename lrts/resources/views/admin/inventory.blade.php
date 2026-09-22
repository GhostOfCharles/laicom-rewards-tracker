@extends('layouts.admin')

@section('content')
<!-- Inventory Section (Wireframe 15) -->
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-bold">INVENTORY</h6>
        <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#addInventoryModal" title="Add Inventory Item"><i class="bi bi-plus-lg"></i></button>
    </div>
    
    <div class="table-responsive border border-dark border-2 p-1">
        <table class="table table-bordered mb-0 text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>PRODUCT NAME</th>
                    <th>STOCK</th>
                    <th>CATEGORY</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start">
                        <div class="d-flex align-items-center">
                            <!-- Placeholder for product image -->
                            <div class="border me-2" style="width: 40px; height: 40px; background-color: #f8f9fa;"></div>
                            DOVE BEAUTY BAR SOAP WHITE 135G
                        </div>
                    </td>
                    <td>550</td>
                    <td>CARE</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" title="Decrease Stock"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editInventoryModal" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">
                        <div class="d-flex align-items-center">
                            <div class="border me-2" style="width: 40px; height: 40px; background-color: #f8f9fa;"></div>
                            KNORR SHRIMP CUBES SINGLES 10G
                        </div>
                    </td>
                    <td>171</td>
                    <td>FOOD</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editInventoryModal"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Inventory Item Modal (Wireframe 16) -->
<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">ADD INVENTORY ITEM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT NAME:</label>
                        <input type="text" class="form-control border-dark rounded-0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">INITIAL STOCK AMOUNT:</label>
                        <input type="number" class="form-control border-dark rounded-0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CATEGORY:</label>
                        <input type="text" class="form-control border-dark rounded-0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" class="form-control border-dark rounded-0">
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

<!-- Edit Inventory Item Modal (Wireframe 17) -->
<div class="modal fade" id="editInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">EDIT INVENTORY ITEM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT NAME:</label>
                        <input type="text" class="form-control border-dark rounded-0" value="DOVE BEAUTY BAR SOAP WHITE 135G">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CURRENT STOCK AMOUNT:</label>
                        <input type="number" class="form-control border-dark rounded-0" value="550">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CATEGORY:</label>
                        <input type="text" class="form-control border-dark rounded-0" value="CARE" placeholder="BEAUTY/PERSONAL CARE / HOME / FOOD">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" class="form-control border-dark rounded-0">
                        <div class="small text-muted mt-1">CURRENT FILE: DOVE_SOAP_135G.PNG</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 justify-content-end">
                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                <button type="button" class="btn btn-dark rounded-0">UPDATE</button>
            </div>
        </div>
    </div>
</div>
@endsection