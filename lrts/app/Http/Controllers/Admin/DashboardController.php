<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PremiumProduct;
use App\Models\Inventory;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $premiumProducts = PremiumProduct::all();
        $promotions = Promotion::with('premiumProduct')->orderBy('created_at', 'desc')->get();
        $inventory = Inventory::orderBy('name')->get();

        return view('admin.dashboard', compact('premiumProducts', 'promotions', 'inventory'));
    }
}