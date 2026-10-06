<tr>
    <td class="text-start fw-bold">
        {{ $promo->title }}
        <span class="badge {{ $promo->is_active ? 'bg-success' : 'bg-secondary' }} rounded-0 ms-1">{{ $promo->is_active ? 'ACTIVE' : 'INACTIVE' }}</span>
    </td>
    <td>{{ $promo->required_quantity }}X {{ $promo->buy_product_name }}</td>
    <td>{{ $promo->reward_quantity }}X {{ \Illuminate\Support\Str::title($promo->premiumProduct->name ?? 'Unknown') }}</td>
    <td>
        <div class="d-flex justify-content-center gap-1 text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPromotionModal{{ $promo->id }}" aria-label="Edit {{ $promo->title }}"><i class="bi bi-pencil-square"></i></button>
            <form action="{{ route('promotions.destroy', $promo->id) }}" method="POST" onsubmit="return confirm('Delete this promotion?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $promo->title }}"><i class="bi bi-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
