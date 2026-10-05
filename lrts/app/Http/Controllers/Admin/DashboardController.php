<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PremiumProduct;
use App\Models\Inventory;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $allPremiumProducts = PremiumProduct::orderBy('name')->get();
        $premiumCategory = $request->query('premium_category');
        $premiumExpiry = $request->query('premium_expiry', '');
        $premiumProducts = PremiumProduct::query()
            ->when(array_key_exists($premiumCategory, PremiumProduct::CATEGORIES), fn ($query) => $query->where('category', $premiumCategory))
            ->when($premiumExpiry === 'perishable', fn ($query) => $query->where('is_perishable', true))
            ->when($premiumExpiry === 'expiring_soon', fn ($query) => $query->where('is_perishable', true)->whereDate('expiry_date', '>=', today()->toDateString())->whereDate('expiry_date', '<=', today()->addDays(30)->toDateString()))
            ->when($premiumExpiry === 'expired', fn ($query) => $query->where('is_perishable', true)->whereDate('expiry_date', '<', today()->toDateString()))
            ->orderBy('name')->get();
        $premiumExpiryAlertCount = $allPremiumProducts->filter(fn (PremiumProduct $product) => $product->isExpired() || $product->isExpiringSoon())->count();
        $promotions = Promotion::with('premiumProduct')->orderBy('created_at', 'desc')->get();
        $inventory = Inventory::orderBy('name')->get();

        return view('admin.dashboard', compact('premiumProducts', 'allPremiumProducts', 'premiumCategory', 'premiumExpiry', 'premiumExpiryAlertCount', 'promotions', 'inventory'));
    }
}
