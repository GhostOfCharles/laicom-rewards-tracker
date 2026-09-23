<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PremiumProduct;
use Illuminate\Http\Request;

class PremiumProductController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'item_code' => 'required|string|unique:premium_products,item_code',
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
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Premium product added successfully!');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $product = PremiumProduct::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $request->name,
            'stock' => $request->stock,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Premium product updated successfully.');
    }

    public function destroy(string $id)
    {
        //
    }
}