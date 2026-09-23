<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.dashboard');
    }

    public function create()
    {
        return redirect()->route('admin.dashboard');
    }

    public function store(Request $request)
    {
        $request->validate([
            'buy_product_name' => 'required|string',
            'required_quantity' => 'required|integer|min:1',
            'premium_product_id' => 'required|exists:premium_products,id',
            'reward_quantity' => 'required|integer|min:1',
            'title' => 'nullable|string'
        ]);

        $title = $request->title ?? "BUY {$request->required_quantity} {$request->buy_product_name} GET {$request->reward_quantity}";

        Promotion::create([
            'title' => $title,
            'buy_product_name' => $request->buy_product_name,
            'required_quantity' => $request->required_quantity,
            'premium_product_id' => $request->premium_product_id,
            'reward_quantity' => $request->reward_quantity,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_active' => 1,
        ]);

        return redirect()->back()->with('success', 'Promotion created successfully.');
    }

    public function show(string $id)
    {
        $promotion = Promotion::with('premiumProduct')->findOrFail($id);
        return response()->json($promotion);
    }

    public function edit(string $id)
    {
        return redirect()->route('admin.dashboard');
    }

    public function update(Request $request, string $id)
    {
        $promo = Promotion::findOrFail($id);

        $request->validate([
            'buy_product_name' => 'required|string',
            'required_quantity' => 'required|integer|min:1',
            'premium_product_id' => 'required|exists:premium_products,id',
            'reward_quantity' => 'required|integer|min:1',
            'title' => 'nullable|string'
        ]);

        $title = $request->title ?? "BUY {$request->required_quantity} {$request->buy_product_name} GET {$request->reward_quantity}";

        $promo->update([
            'title' => $title,
            'buy_product_name' => $request->buy_product_name,
            'required_quantity' => $request->required_quantity,
            'premium_product_id' => $request->premium_product_id,
            'reward_quantity' => $request->reward_quantity,
        ]);

        return redirect()->back()->with('success', 'Promotion updated successfully.');
    }

    public function destroy(string $id)
    {
        Promotion::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Promotion removed.');
    }
}