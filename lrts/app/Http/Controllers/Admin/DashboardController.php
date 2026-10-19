<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $allPremiumProducts = PremiumProduct::query()->select(['id', 'name', 'item_code', 'stock', 'image_path', 'category', 'is_perishable', 'expiry_date'])->orderByRaw('LOWER(name)')->orderBy('item_code')->get();
        $premiumCategory = $request->query('premium_category', '');
        $premiumCategory = is_string($premiumCategory) ? $premiumCategory : '';
        $premiumCategory = array_key_exists($premiumCategory, PremiumProduct::CATEGORIES) ? $premiumCategory : '';
        $premiumExpiry = in_array($request->query('premium_expiry', ''), ['', 'perishable', 'expiring_soon', 'expired'], true)
            ? $request->query('premium_expiry', '')
            : '';
        $premiumStock = in_array($request->query('premium_stock', 'all'), ['all', 'high', 'mid', 'low', 'out'], true)
            ? $request->query('premium_stock', 'all')
            : 'all';
        $premiumAvailability = in_array($request->query('premium_availability', 'all'), ['all', 'available', 'out'], true)
            ? $request->query('premium_availability', 'all')
            : 'all';
        $premiumSearchInput = $request->query('premium_search', '');
        $premiumSearch = mb_substr(trim(is_string($premiumSearchInput) ? $premiumSearchInput : ''), 0, 100);

        $premiumQuery = PremiumProduct::query()->withSum(['earnedRewards as reserved_quantity' => fn ($q) => $q->whereIn('claim_status', ['unclaimed', 'claim_requested'])], 'reward_quantity')
            ->when(array_key_exists($premiumCategory, PremiumProduct::CATEGORIES), fn ($query) => $query->where('category', $premiumCategory))
            ->when($premiumSearch !== '', function ($query) use ($premiumSearch) {
                $term = mb_strtolower($premiumSearch);
                $query->where(function ($query) use ($term) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%'.$term.'%'])
                        ->orWhereRaw('LOWER(item_code) LIKE ?', ['%'.$term.'%']);
                });
            })
            ->when($premiumStock === 'high', fn ($query) => $query->where('stock', '>', 50))
            ->when($premiumStock === 'mid', fn ($query) => $query->whereBetween('stock', [11, 50]))
            ->when($premiumStock === 'low', fn ($query) => $query->whereBetween('stock', [1, 10]))
            ->when($premiumStock === 'out', fn ($query) => $query->where('stock', 0))
            ->when($premiumAvailability === 'available', fn ($query) => $query->where('stock', '>', 0))
            ->when($premiumAvailability === 'out', fn ($query) => $query->where('stock', 0))
            ->when($premiumExpiry === 'perishable', fn ($query) => $query->where('is_perishable', true))
            ->when($premiumExpiry === 'expiring_soon', fn ($query) => $query->where('is_perishable', true)->whereDate('expiry_date', '>=', today()->toDateString())->whereDate('expiry_date', '<=', today()->addDays(30)->toDateString()))
            ->when($premiumExpiry === 'expired', fn ($query) => $query->where('is_perishable', true)->whereDate('expiry_date', '<', today()->toDateString()))
            ->orderByRaw('LOWER(name)')->orderBy('item_code');
        $premiumProducts = (clone $premiumQuery)->paginate(20, ['*'], 'premium_page')->withQueryString();
        $visiblePremiumProducts = (clone $premiumQuery)->limit(5)->get();
        $editablePremiumProducts = $premiumProducts->getCollection()->merge($visiblePremiumProducts)->unique('id');
        $premiumExpiryAlertCount = PremiumProduct::where('is_perishable', true)->whereDate('expiry_date', '<=', today()->addDays(30))->count();

        $promotionSearchInput = $request->query('promotion_search', '');
        $promotionSearch = mb_substr(trim(is_string($promotionSearchInput) ? $promotionSearchInput : ''), 0, 100);
        $promotionStatus = in_array($request->query('promotion_status', 'all'), ['all', 'active', 'inactive'], true)
            ? $request->query('promotion_status', 'all')
            : 'all';
        $promotionQuery = Promotion::with('premiumProduct')
            ->when($promotionSearch !== '', function ($query) use ($promotionSearch) {
                $term = mb_strtolower($promotionSearch);
                $query->where(function ($query) use ($term) {
                    $query->whereRaw('LOWER(title) LIKE ?', ['%'.$term.'%'])
                        ->orWhereRaw('LOWER(buy_product_name) LIKE ?', ['%'.$term.'%'])
                        ->orWhereHas('premiumProduct', fn ($productQuery) => $productQuery->whereRaw('LOWER(name) LIKE ?', ['%'.$term.'%']));
                });
            })
            ->when($promotionStatus === 'active', fn ($query) => $query->where('is_active', true))
            ->when($promotionStatus === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('created_at', 'desc');
        $promotions = (clone $promotionQuery)->paginate(20, ['*'], 'promotions_page')->withQueryString();
        $visiblePromotions = (clone $promotionQuery)->limit(5)->get();
        $editablePromotions = $promotions->getCollection()->merge($visiblePromotions)->unique('id');
        $premiumProductCount = $premiumProducts->total();
        $promotionCount = $promotions->total();
        $overlapCount = Promotion::where('is_active', true)->whereExists(function ($q) {
            $q->selectRaw('1')->from('promotions as other')->where('other.is_active', true)->whereColumn('other.id', '<>', 'promotions.id')->whereRaw('(other.inventory_id = promotions.inventory_id OR (other.inventory_id IS NULL AND promotions.inventory_id IS NULL AND other.buy_product_name = promotions.buy_product_name))')->where(fn ($q) => $q->whereNull('other.start_date')->orWhereNull('promotions.end_date')->orWhereColumn('other.start_date', '<=', 'promotions.end_date'))->where(fn ($q) => $q->whereNull('other.end_date')->orWhereNull('promotions.start_date')->orWhereColumn('other.end_date', '>=', 'promotions.start_date'));
        })->count();
        $inventory = Inventory::orderBy('name')->get();

        return view('admin.dashboard', compact(
            'premiumProducts', 'allPremiumProducts', 'premiumCategory', 'premiumExpiry', 'premiumExpiryAlertCount',
            'premiumStock', 'premiumAvailability', 'premiumSearch', 'visiblePremiumProducts', 'premiumProductCount',
            'editablePremiumProducts', 'editablePromotions', 'overlapCount', 'promotions', 'visiblePromotions', 'promotionCount', 'promotionSearch', 'promotionStatus', 'inventory'
        ));
    }
}
