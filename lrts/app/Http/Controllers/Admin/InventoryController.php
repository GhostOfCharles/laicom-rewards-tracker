<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Services\ActivityLogger;
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

    public function store(Request $request, ActivityLogger $activityLogger)
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

        DB::transaction(function () use ($request, $imagePath, $activityLogger) {
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
            $activityLogger->record('inventory.created', 'Inventory item ' . $item->name . ' created.', $item, $item->only(['name', 'category', 'stock_balance', 'entry_date', 'expiry_date']));
        });

        return redirect()->route('admin.inventory')->with('success', 'Inventory item added successfully.');
    }

    public function update(Request $request, string $id, ActivityLogger $activityLogger)
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

        DB::transaction(function () use ($request, $item, $imagePath, $activityLogger) {
            $item = Inventory::query()->lockForUpdate()->findOrFail($item->id);
            $oldStock = (int) $item->stock_balance;
            $newStock = (int) $request->stock_balance;
            $before = $item->only(['name', 'category', 'entry_date', 'expiry_date', 'image_path']);

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
                $activityLogger->record('inventory.stock_adjusted', 'Stock adjusted for inventory item ' . $item->name . '.', $item, ['old' => $oldStock, 'new' => $newStock, 'delta' => $newStock - $oldStock]);
            }

            $changes = [];
            foreach (['name', 'category', 'entry_date', 'expiry_date', 'image_path'] as $field) {
                $oldValue = $before[$field] instanceof \Carbon\CarbonInterface ? $before[$field]->toDateString() : $before[$field];
                $newValue = $item->getAttribute($field) instanceof \Carbon\CarbonInterface ? $item->getAttribute($field)->toDateString() : $item->getAttribute($field);
                if ($oldValue != $newValue) $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
            if ($changes) $activityLogger->record('inventory.updated', 'Inventory item ' . $item->name . ' updated.', $item, ['changes' => $changes]);
        });

        return redirect()->route('admin.inventory')->with('success', 'Inventory item updated.');
    }

    public function destroy(string $id, ActivityLogger $activityLogger)
    {
        DB::transaction(function () use ($id, $activityLogger) {
            $item = Inventory::query()->lockForUpdate()->findOrFail($id);
            $properties = $item->only(['name', 'category', 'stock_balance']);
            $name = $item->name;
            $item->delete();
            $activityLogger->record('inventory.deleted', 'Inventory item ' . $name . ' deleted.', $item, $properties);
        });
        return redirect()->route('admin.inventory')->with('success', 'Inventory item removed.');
    }
}
