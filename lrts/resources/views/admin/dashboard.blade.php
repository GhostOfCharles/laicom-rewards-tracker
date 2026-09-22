@extends('layouts.admin')

@section('content')
<!-- Premium Products Section -->
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-bold">PREMIUM PRODUCTS</h6>
        <button class="btn btn-sm btn-outline-dark" title="Add New Premium Product"><i class="bi bi-plus-lg"></i></button>
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
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">BLUE DIPPER</td>
                    <td>20</td>
                    <td class="text-danger fw-bold">LOW</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">BREEZE 90G</td>
                    <td>70</td>
                    <td class="text-muted fw-bold">-</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></button>
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
        <button class="btn btn-sm btn-outline-dark" title="Add Active Promotion"><i class="bi bi-plus-lg"></i></button>
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
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">BUY 3 SUNSILK SHAMPOO SMOOTH GET 1</td>
                    <td>3</td>
                    <td>1</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
                <tr>
                    <td class="text-start">BUY KNORR CUBES 10G</td>
                    <td>4</td>
                    <td>1</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-dash"></i></button>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection