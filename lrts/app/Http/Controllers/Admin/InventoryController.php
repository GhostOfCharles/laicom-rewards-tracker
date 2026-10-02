<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');
        $inventory = Inventory::query()
            ->when(array_key_exists($category, Inventory::CATEGORIES), fn ($query) => $query->where('category', $category))
            ->orderBy('name')->get();
        return view('admin.inventory', compact('inventory', 'category'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => ['required', 'in:' . implode(',', array_keys(Inventory::CATEGORIES))],
            'stock_balance' => 'required|integer|min:0',
            'entry_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after_or_equal:entry_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('inventory', 'public');
        }

        DB::transaction(function () use ($request, $imagePath) {
            $item = Inventory::create([
                'name' => $request->name,
                'category' => $request->category,
                'stock_balance' => $request->stock_balance,
                'reserved_stock' => 0,
                'image_path' => $imagePath,
                'entry_date' => $request->input('entry_date'),
                'expiry_date' => $request->input('expiry_date'),
            ]);

            if ($item->stock_balance > 0) {
                $item->stockMovements()->create([
                    'type' => 'inflow',
                    'quantity' => $item->stock_balance,
                    'reference' => 'Admin add',
                    'notes' => 'Initial stock recorded when inventory item was created.',
                ]);
            }
        });

        return redirect()->route('admin.inventory')->with('success', 'Inventory item added successfully.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => ['required', 'in:' . implode(',', array_keys(Inventory::CATEGORIES))],
            'stock_balance' => 'required|integer|min:0',
            'entry_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after_or_equal:entry_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $item = Inventory::findOrFail($id);
        $imagePath = $item->image_path;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('inventory', 'public');
        }

        DB::transaction(function () use ($request, $item, $imagePath) {
            $item = Inventory::query()->lockForUpdate()->findOrFail($item->id);
            $oldStock = (int) $item->stock_balance;
            $newStock = (int) $request->stock_balance;

            $item->update([
                'name' => $request->name,
                'category' => $request->category,
                'stock_balance' => $newStock,
                'image_path' => $imagePath,
                'entry_date' => $request->input('entry_date'),
                'expiry_date' => $request->input('expiry_date'),
            ]);

            if ($newStock !== $oldStock) {
                $item->stockMovements()->create([
                    'type' => $newStock > $oldStock ? 'inflow' : 'outflow',
                    'quantity' => abs($newStock - $oldStock),
                    'reference' => 'Admin edit',
                    'notes' => 'Stock balance changed from ' . $oldStock . ' to ' . $newStock . '.',
                ]);
            }
        });

        return redirect()->route('admin.inventory')->with('success', 'Inventory item updated.');
    }

    public function destroy(string $id)
    {
        Inventory::findOrFail($id)->delete();
        return redirect()->route('admin.inventory')->with('success', 'Inventory item removed.');
    }
}
