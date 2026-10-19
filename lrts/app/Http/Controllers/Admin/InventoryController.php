<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Promotion;
use App\Services\ActivityLogger;
use App\Services\ProductImageStorage;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['category' => 'nullable|in:'.implode(',', array_keys(Inventory::CATEGORIES)), 'search' => 'nullable|string|max:100']);
        $category = $request->query('category');
        $search = trim((string) $request->query('search', ''));
        $inventory = Inventory::query()
            ->when(array_key_exists($category, Inventory::CATEGORIES), fn ($query) => $query->where('category', $category))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.inventory', compact('inventory', 'category', 'search'));
    }

    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) use ($request) {
                if (Inventory::where('name_key', Inventory::nameKey($value))->when($request->route('id'), fn ($q, $id) => $q->whereKeyNot($id))->exists()) {
                    $fail('A product with this name already exists.');
                }
            }],
            'category' => ['required', 'in:'.implode(',', array_keys(Inventory::CATEGORIES))],
            'stock_balance' => 'required|integer|min:0',
            'entry_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after_or_equal:entry_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = app(ProductImageStorage::class)->store($request->file('image'), 'inventory');
        }

        $previousImagePath = null;
        try {
            DB::transaction(function () use ($request, $imagePath, $activityLogger) {
                $item = Inventory::create([
                    'name' => $request->name,
                    'category' => $request->category,
                    'stock_balance' => $request->stock_balance,
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
                $activityLogger->record('inventory.created', 'Inventory item '.$item->name.' created.', $item, $item->only(['name', 'category', 'stock_balance', 'entry_date', 'expiry_date']));
            });
        } catch (\Throwable $exception) {
            if ($imagePath && $imagePath !== $previousImagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $exception;
        }

        return redirect()->route('admin.inventory')->with('success', 'Inventory item added successfully.');
    }

    public function update(Request $request, string $id, ActivityLogger $activityLogger)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) use ($request) {
                if (Inventory::where('name_key', Inventory::nameKey($value))->when($request->route('id'), fn ($q, $id) => $q->whereKeyNot($id))->exists()) {
                    $fail('A product with this name already exists.');
                }
            }],
            'category' => ['required', 'in:'.implode(',', array_keys(Inventory::CATEGORIES))],
            'stock_balance' => 'required|integer|min:0',
            'expected_stock' => 'required|integer|min:0',
            'entry_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after_or_equal:entry_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $item = Inventory::findOrFail($id);
        $imagePath = $item->image_path;
        if ($request->hasFile('image')) {
            $imagePath = app(ProductImageStorage::class)->store($request->file('image'), 'inventory');
        }

        $previousImagePath = $item->image_path;
        try {
            DB::transaction(function () use ($request, $item, $imagePath, $activityLogger) {
                $item = Inventory::query()->lockForUpdate()->findOrFail($item->id);
                if ((int) $request->expected_stock !== (int) $item->stock_balance) {
                    throw ValidationException::withMessages(['stock_balance' => 'Stock changed since this form was opened. Refresh and try again.']);
                }
                $oldName = $item->name;
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
                        'notes' => 'Stock balance changed from '.$oldStock.' to '.$newStock.'.',
                    ]);
                    $activityLogger->record('inventory.stock_adjusted', 'Stock adjusted for inventory item '.$item->name.'.', $item, ['old' => $oldStock, 'new' => $newStock, 'delta' => $newStock - $oldStock]);
                }

                Promotion::where('inventory_id', $item->id)->update(['buy_product_name' => $item->name]);
                $changes = [];
                foreach (['name', 'category', 'entry_date', 'expiry_date', 'image_path'] as $field) {
                    $oldValue = $before[$field] instanceof CarbonInterface ? $before[$field]->toDateString() : $before[$field];
                    $newValue = $item->getAttribute($field) instanceof CarbonInterface ? $item->getAttribute($field)->toDateString() : $item->getAttribute($field);
                    if ($oldValue != $newValue) {
                        $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
                    }
                }
                if ($changes) {
                    $activityLogger->record('inventory.updated', 'Inventory item '.$item->name.' updated.', $item, ['changes' => $changes]);
                }
            });
        } catch (\Throwable $exception) {
            if ($imagePath && $imagePath !== $previousImagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $exception;
        }

        return redirect()->route('admin.inventory')->with('success', 'Inventory item updated.');
    }

    public function destroy(string $id, ActivityLogger $activityLogger)
    {
        DB::transaction(function () use ($id, $activityLogger) {
            $item = Inventory::query()->lockForUpdate()->findOrFail($id);
            if (Promotion::where('inventory_id', $item->id)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['error' => 'Deactivate promotions for this product before deleting it.']);
            }
            $properties = $item->only(['name', 'category', 'stock_balance']);
            $name = $item->name;
            $item->delete();
            $activityLogger->record('inventory.deleted', 'Inventory item '.$name.' deleted.', $item, $properties);
        });

        return redirect()->route('admin.inventory')->with('success', 'Inventory item removed.');
    }
}
