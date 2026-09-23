<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $inventory = Inventory::orderBy('name')->get();
        return view('admin.inventory', compact('inventory'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'stock_balance' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('inventory', 'public');
        }

        Inventory::create([
            'name' => $request->name,
            'category' => $request->category,
            'stock_balance' => $request->stock_balance,
            'reserved_stock' => 0,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.inventory')->with('success', 'Inventory item added successfully.');
    }

    public function update(Request $request, string $id)
    {
        $item = Inventory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'stock_balance' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = $item->image_path;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('inventory', 'public');
        }

        $item->update([
            'name' => $request->name,
            'category' => $request->category,
            'stock_balance' => $request->stock_balance,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.inventory')->with('success', 'Inventory item updated.');
    }

    public function destroy(string $id)
    {
        Inventory::findOrFail($id)->delete();
        return redirect()->route('admin.inventory')->with('success', 'Inventory item removed.');
    }
}