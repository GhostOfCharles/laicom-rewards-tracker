<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PremiumProduct;
use App\Models\Inventory;
use Illuminate\Http\Request;

class PremiumProductController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'item_code' => 'required|string|unique:premium_products,item_code',
            'initial_stock' => 'required|integer|min:0',
            'category' => ['required', 'in:' . implode(',', array_keys(Inventory::CATEGORIES))],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        PremiumProduct::create([
            'name' => $request->name,
            'item_code' => $request->item_code,
            'image_path' => $imagePath,
            'stock' => $request->initial_stock ?? 0,
            'category' => $request->category,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Premium product added successfully!');
    }

    public function update(Request $request, string $id)
    {
        $product = PremiumProduct::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
            'category' => ['required', 'in:' . implode(',', array_keys(Inventory::CATEGORIES))],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $request->name,
            'stock' => $request->stock,
            'category' => $request->category,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Premium product updated successfully.');
    }

    public function destroy(string $id)
    {
        $product = PremiumProduct::withCount(['promotions', 'earnedRewards'])->findOrFail($id);

        if ($product->promotions_count > 0 || $product->earned_rewards_count > 0) {
            return back()->withErrors(['error' => 'This premium product cannot be deleted because it is used by promotions or reward records.']);
        }

        $product->delete();
        return back()->with('success', 'Premium product removed.');
    }
}
