@extends('layouts.admin')

@section('content')

@if(session('success'))
    <div class="alert alert-success py-2 small fw-bold rounded-0 mb-4">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger py-2 small fw-bold rounded-0 mb-4">{{ $errors->first() }}</div>
@endif

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
                @forelse($inventory as $item)
                <tr>
                    <td class="text-start">
                        <div class="d-flex align-items-center">
                            <div class="border me-2" style="width: 40px; height: 40px; background-color: #f8f9fa; background-size: cover; background-position: center;
                                @if($item->image_path) background-image: url('{{ asset('storage/' . $item->image_path) }}'); @endif">
                            </div>
                            <span class="fw-bold">{{ $item->name }}</span>
                        </div>
                    </td>
                    <td>{{ $item->stock_balance }}</td>
                    <td>{{ $item->category }}</td>
                    <td>
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editInventoryModal{{ $item->id }}">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="{{ route('admin.inventory.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this item?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-3 text-muted fw-bold">NO INVENTORY ITEMS FOUND</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Inventory Item Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">ADD INVENTORY ITEM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form action="{{ route('admin.inventory.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT NAME:</label>
                        <input type="text" name="name" class="form-control border-dark rounded-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">INITIAL STOCK AMOUNT:</label>
                        <input type="number" name="stock_balance" class="form-control border-dark rounded-0" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CATEGORY:</label>
                        <input type="text" name="category" class="form-control border-dark rounded-0" placeholder="BEAUTY/PERSONAL CARE / HOME / FOOD" required>
                    </div>
                    <div class="mb-3">
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

<!-- Dynamic Edit Inventory Modals -->
@foreach($inventory as $item)
<div class="modal fade" id="editInventoryModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-dark border-2 rounded-0">
            <div class="modal-header border-bottom border-dark">
                <h5 class="modal-title fw-bold">EDIT INVENTORY ITEM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form action="{{ route('admin.inventory.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT NAME:</label>
                        <input type="text" name="name" class="form-control border-dark rounded-0" value="{{ $item->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CURRENT STOCK AMOUNT:</label>
                        <input type="number" name="stock_balance" class="form-control border-dark rounded-0" value="{{ $item->stock_balance }}" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">CATEGORY:</label>
                        <input type="text" name="category" class="form-control border-dark rounded-0" value="{{ $item->category }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" name="image" class="form-control border-dark rounded-0" accept="image/*">
                        @if($item->image_path)
                            <div class="small text-muted mt-1">CURRENT: {{ basename($item->image_path) }}</div>
                        @endif
                    </div>
                    <div class="modal-footer border-top-0 justify-content-end px-0 pb-0">
                        <button type="button" class="btn btn-outline-dark rounded-0" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn btn-dark rounded-0">UPDATE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection