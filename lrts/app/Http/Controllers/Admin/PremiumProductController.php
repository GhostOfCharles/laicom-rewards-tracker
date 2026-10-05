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
            'is_perishable' => 'required|boolean',
            'entry_date' => ['nullable', 'date', 'required_if:is_perishable,1', 'before_or_equal:' . today()->addYear()->toDateString()],
            'expiry_date' => ['nullable', 'date', 'required_if:is_perishable,1', 'after:entry_date', 'before_or_equal:' . today()->addYears(5)->toDateString()],
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
            'is_perishable' => $request->boolean('is_perishable'),
            'entry_date' => $request->boolean('is_perishable') ? $request->input('entry_date') : null,
            'expiry_date' => $request->boolean('is_perishable') ? $request->input('expiry_date') : null,
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
            'is_perishable' => 'required|boolean',
            'entry_date' => ['nullable', 'date', 'required_if:is_perishable,1', 'before_or_equal:' . today()->addYear()->toDateString()],
            'expiry_date' => ['nullable', 'date', 'required_if:is_perishable,1', 'after:entry_date', 'before_or_equal:' . today()->addYears(5)->toDateString()],
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
            'is_perishable' => $request->boolean('is_perishable'),
            'entry_date' => $request->boolean('is_perishable') ? $request->input('entry_date') : null,
            'expiry_date' => $request->boolean('is_perishable') ? $request->input('expiry_date') : null,
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
