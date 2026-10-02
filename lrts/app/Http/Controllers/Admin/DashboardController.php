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
        $premiumProducts = PremiumProduct::query()
            ->when(array_key_exists($premiumCategory, PremiumProduct::CATEGORIES), fn ($query) => $query->where('category', $premiumCategory))
            ->orderBy('name')->get();
        $promotions = Promotion::with('premiumProduct')->orderBy('created_at', 'desc')->get();
        $inventory = Inventory::orderBy('name')->get();

        return view('admin.dashboard', compact('premiumProducts', 'allPremiumProducts', 'premiumCategory', 'promotions', 'inventory'));
    }
}
