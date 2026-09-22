@extends('layouts.admin')

@section('content')
<!-- Receipts Queue (Wireframe 19) -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <label class="fw-bold small me-2 mb-0">SORT BY:</label>
        <select class="form-select form-select-sm border-dark rounded-0 w-auto fw-bold">
            <option>PROCESSING</option>
            <option>APPROVED</option>
        </select>
    </div>
    
    <div class="border border-dark border-2 p-2">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr class="border-bottom border-dark">
                    <td class="text-start pb-3">
                        <div class="fw-bold">ORDER NO: LKM-94B17Z</div>
                        <div class="small fw-bold mt-1">CUSTOMER: JOHN DOE</div>
                        <div class="small fw-bold">DATE SUBMITTED: XX/XX/XX</div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-dark rounded-0 fw-bold me-2" data-bs-toggle="modal" data-bs-target="#viewProductsModal">VIEW PRODUCTS</button>
                            <button class="btn btn-sm btn-outline-dark rounded-0 fw-bold" data-bs-toggle="modal" data-bs-target="#viewRewardsModal">VIEW CLAIMABLE REWARDS</button>
                        </div>
                    </td>
                    <td class="text-end align-middle pb-3" style="width: 150px;">
                        <div class="badge bg-warning text-dark border border-dark rounded-0 w-100 py-2 mb-2">STATUS: PENDING</div>
                        <button class="btn btn-sm btn-success rounded-0 w-100 mb-1 fw-bold">APPROVE</button>
                        <button class="btn btn-sm btn-danger rounded-0 w-100 fw-bold">REJECT</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- View Products Modal (Wireframe 20) -->
<div class="modal fade" id="viewProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h6 class="modal-title fw-bold">PURCHASED PRODUCTS | ORDER #: LKM-94B17Z</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-start">
                <div class="small fw-bold mb-1">CUSTOMER: JOHN DOE</div>
                <div class="small fw-bold mb-3">DATE SUBMITTED: XX/XX/XX</div>
                <h6 class="fw-bold border-bottom border-dark pb-1">LOGGED ITEMS:</h6>
                <ul class="list-unstyled small fw-bold">
                    <li>• 12X DOVE BEAUTY BAR SOAP WHITE 135G</li>
                    <li>• 6X BODYSPRAY ICE CHILL 50ML</li>
                    <li>• 4X BREEZE POWDER DETERGENT 200G</li>
                </ul>
            </div>
            <div class="modal-footer border-top-0 justify-content-end">
                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>

<!-- View Claimable Rewards Modal (Wireframe 21) -->
<div class="modal fade" id="viewRewardsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h6 class="modal-title fw-bold">CLAIMABLE REWARDS | ORDER #: LKM-94B17Z</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-start">
                <h6 class="fw-bold border-bottom border-dark pb-1 mb-3">PRE-CALCULATED REWARD ITEMS:</h6>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="small fw-bold">• 2X FREE PLASTIC DIPPER</div>
                    <div class="small fw-bold text-muted">(FIXED)</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="small fw-bold">• 1X FREE DOVE SOAP BAR 90G</div>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" data-bs-toggle="modal" data-bs-target="#editPromoOptionModal" title="Edit Alternate Option"><i class="bi bi-pencil-square"></i></button>
                </div>
                <div class="small text-muted fst-italic" style="font-size: 0.7rem;">TIED TO DOVE SOAP BAR PURCHASE. CLICK PENCIL TO OFFER ALTERNATE.</div>
            </div>
            <div class="modal-footer border-top-0 justify-content-end">
                <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Promo Option Modal (Wireframe 22) -->
<div class="modal fade" id="editPromoOptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-body text-start p-2">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="small fw-bold">• 1X FREE DOVE SOAP BAR 90G</div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="small text-muted fst-italic border-bottom border-dark pb-1 mb-2" style="font-size: 0.7rem;">
                    TIED TO DOVE SOAP BAR PURCHASE. CLICK PENCIL TO OFFER ALTERNATE.
                </div>
                
                <label class="small fw-bold mb-1">SWITCH DOVE SOAP BAR 90G TO:</label>
                <select class="form-select form-select-sm border-dark rounded-0 mb-3" size="4">
                    <option>1X FREE SHAMPOO 180ML</option>
                    <option selected>2X FREE PLASTIC DIPPER (DEFAULT)</option>
                    <option>1X FREE SHAMPOO 180ML</option>
                    <option>1X FREE FABRIC CONDITIONER 25ML</option>
                </select>
            </div>
            <div class="modal-footer border-top-0 justify-content-end p-2">
                <button type="button" class="btn btn-sm btn-outline-dark rounded-0" data-bs-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>
@endsection