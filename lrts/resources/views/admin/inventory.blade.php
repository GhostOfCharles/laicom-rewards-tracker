@extends('layouts.admin')

@section('content')

<!-- Inventory Section (Wireframe 15) -->
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div><h6 class="mb-0 fw-bold">INVENTORY</h6><span class="small text-muted">Customer-purchased products</span></div>
        <button type="button" class="btn btn-sm laicom-btn-primary" data-bs-toggle="modal" data-bs-target="#addInventoryModal" title="Add Inventory Item" aria-label="Add inventory item"><i class="bi bi-plus-lg"></i></button>
    </div>

    <form method="GET" action="{{ route('admin.inventory') }}" class="d-flex align-items-center gap-2 mb-2 lrts-inventory-filter">
        <label for="category" class="small fw-bold mb-0">SORT BY CATEGORY</label>
        <select id="category" name="category" class="form-select form-select-sm border-dark border-2 rounded-0 w-auto" onchange="this.form.submit()">
            <option value="">All categories</option>
            @foreach (\App\Models\Inventory::CATEGORIES as $value => $label)
                <option value="{{ $value }}" {{ $category === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="table-responsive laicom-card p-1">
        <table class="table table-bordered laicom-table mb-0 text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">PRODUCT NAME</th>
                    <th scope="col">STOCK</th>
                    <th scope="col">CATEGORY</th>
                    <th scope="col">ENTRY DATE</th>
                    <th scope="col">EXPIRY DATE</th>
                    <th scope="col">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventory as $item)
                <tr>
                    <td class="text-start">
                        <div class="d-flex align-items-center">
                            <img src="{{ $item->image_path ? asset('storage/' . $item->image_path) : asset('images/laicom-logo.png') }}" onerror="this.src='{{ asset('images/laicom-logo.png') }}'" alt="{{ $item->name }}" class="laicom-thumbnail {{ $item->image_path ? '' : 'laicom-empty-image' }} me-2">
                            <span class="fw-bold">{{ $item->name }}</span>
                        </div>
                    </td>
                    <td>{{ $item->stock_balance }}</td>
                    <td>{{ $item->categoryLabel() }}</td>
                    <td>{{ $item->entry_date?->format('M d, Y') ?? '—' }}</td>
                    <td>
                        @if ($item->expiry_date)
                            @if ($item->expiry_date->lt(today()))
                                <span class="badge bg-danger">EXPIRED</span><span class="d-block small">{{ $item->expiry_date->format('M d, Y') }}</span>
                            @elseif ($item->expiry_date->lte(now()->addDays(30)->startOfDay()))
                                <span class="badge bg-warning text-dark">EXPIRING SOON</span><span class="d-block small">{{ $item->expiry_date->format('M d, Y') }}</span>
                            @else
                                {{ $item->expiry_date->format('M d, Y') }}
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
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
                    <td colspan="6" class="text-center py-3 text-muted fw-bold">NO INVENTORY ITEMS FOUND</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Inventory Item Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content laicom-modal">
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
                        <select name="category" class="form-select border-dark border-2 rounded-0" required>
                            <option value="" selected disabled>Select a category</option>
                            @foreach (\App\Models\Inventory::CATEGORIES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">ENTRY DATE:</label>
                        <input type="date" name="entry_date" class="form-control border-dark border-2 rounded-0" max="{{ now()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">EXPIRY DATE:</label>
                        <input type="date" name="expiry_date" class="form-control border-dark border-2 rounded-0" min="{{ now()->toDateString() }}">
                    </div>
                    <div class="mb-3">
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

<!-- Dynamic Edit Inventory Modals -->
@foreach($inventory as $item)
<div class="modal fade" id="editInventoryModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content laicom-modal">
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
                        <select name="category" class="form-select border-dark border-2 rounded-0" required>
                            @if (! array_key_exists($item->category, \App\Models\Inventory::CATEGORIES))
                                <option value="" selected disabled>Select a category for this legacy item</option>
                            @endif
                            @foreach (\App\Models\Inventory::CATEGORIES as $value => $label)<option value="{{ $value }}" {{ $item->category === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">ENTRY DATE:</label>
                        <input type="date" name="entry_date" class="form-control border-dark border-2 rounded-0" value="{{ $item->entry_date?->format('Y-m-d') }}" max="{{ now()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">EXPIRY DATE:</label>
                        <input type="date" name="expiry_date" class="form-control border-dark border-2 rounded-0" value="{{ $item->expiry_date?->format('Y-m-d') }}" min="{{ $item->entry_date?->format('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">PRODUCT PICTURE:</label>
                        <input type="file" name="image" class="form-control border-dark rounded-0" accept="image/png,image/jpeg,image/gif">
                        @if ($item->image_path)
                            <div class="small text-muted mt-2 d-flex align-items-center gap-2"><img src="{{ asset('storage/' . $item->image_path) }}" alt="Current {{ $item->name }} image" class="laicom-thumbnail">Current image</div>
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
