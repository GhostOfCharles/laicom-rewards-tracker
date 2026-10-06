<tr>
    <td class="text-start fw-bold">
        <img src="{{ $premium->image_path ? asset('storage/' . $premium->image_path) : asset('images/laicom-logo.png') }}" onerror="this.src='{{ asset('images/laicom-logo.png') }}'" alt="{{ $premium->name }}" class="laicom-thumbnail {{ $premium->image_path ? '' : 'laicom-empty-image' }} me-2">
        <span>{{ \Illuminate\Support\Str::title($premium->name) }}</span>
        <small class="catalog-item-code d-block ms-5">{{ $premium->item_code }}</small>
    </td>
    <td>{{ $premium->categoryLabel() }}</td>
    <td>{{ $premium->stock }}</td>
    <td>
        @if($premium->stock > 50)
            <span class="badge bg-success rounded-0 stock-status-badge">HIGH</span>
        @elseif($premium->stock > 10)
            <span class="badge bg-warning text-dark rounded-0 stock-status-badge">MID</span>
        @elseif($premium->stock > 0)
            <span class="badge bg-danger rounded-0 stock-status-badge">LOW</span>
        @else
            <span class="badge bg-secondary rounded-0 stock-status-badge">OUT OF STOCK</span>
        @endif
    </td>
    <td>
        @if($premium->expiryStatus() === 'non_perishable')
            <span class="small text-muted">N/A</span>
        @elseif($premium->expiryStatus() === 'expired')
            <span class="badge bg-danger rounded-0">EXPIRED</span>
        @elseif($premium->expiryStatus() === 'expiring_soon')
            <span class="badge bg-warning text-dark rounded-0">{{ $premium->daysUntilExpiry() }} days left</span>
        @else
            <span class="small text-muted">{{ $premium->expiry_date?->format('M d, Y') ?? 'Date unavailable' }}</span>
        @endif
    </td>
    <td>
        <div class="d-flex justify-content-center gap-1 text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPremiumProductModal{{ $premium->id }}" aria-label="Edit {{ $premium->name }}"><i class="bi bi-pencil-square"></i></button>
            <form action="{{ route('products.destroy', $premium->id) }}" method="POST" onsubmit="return confirm('Delete this premium product? Products used by rewards cannot be deleted.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $premium->name }}"><i class="bi bi-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
