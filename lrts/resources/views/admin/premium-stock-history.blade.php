@extends('layouts.admin')

@section('content')
<section class="laicom-card p-3">
    <div class="d-flex justify-content-between align-items-start border-bottom border-dark pb-2 mb-3"><div><h5 class="fw-bold mb-1">PREMIUM STOCK HISTORY</h5><div>{{ $product->name }} · {{ $product->item_code }} · Current balance: <strong>{{ number_format($product->stock) }}</strong></div></div><button class="btn btn-sm btn-outline-dark rounded-0" onclick="window.close()">CLOSE</button></div>
    <div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>DATE</th><th>TYPE</th><th>CHANGE</th><th>BALANCE</th><th>STAFF</th><th>NOTES</th></tr></thead><tbody>
        @forelse($movements as $movement)<tr><td>{{ $movement->created_at?->format('M d, Y H:i:s') }}</td><td>{{ strtoupper(str_replace('_', ' ', $movement->type)) }}</td><td class="{{ $movement->quantity < 0 ? 'text-danger' : 'text-success' }} fw-bold">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td><td>{{ $movement->balance_after }}</td><td>{{ $movement->user?->name ?? 'System' }}</td><td>{{ $movement->notes ?? '—' }}</td></tr>@empty<tr><td colspan="6" class="text-center py-4">No movements recorded.</td></tr>@endforelse
    </tbody></table></div><div>{{ $movements->links() }}</div>
</section>
@endsection
