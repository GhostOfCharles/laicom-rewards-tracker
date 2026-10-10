<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Services\ActivityLogger;
use App\Services\PremiumStockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PremiumProductController extends Controller
{
    public function stockHistory(PremiumProduct $product): View
    {
        $movements = $product->stockMovements()->with('user')->latest('created_at')->paginate(50);
        return view('admin.premium-stock-history', compact('product', 'movements'));
    }

    public function store(Request $request, PremiumStockLedger $ledger, ActivityLogger $activityLogger)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', $this->uniqueProductNameRule()],
            'item_code' => 'required|string|unique:premium_products,item_code',
            'initial_stock' => 'required|integer|min:0',
            'category' => ['required', 'in:' . implode(',', array_keys(Inventory::CATEGORIES))],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_perishable' => 'required|boolean',
            'entry_date' => ['nullable', 'date', 'required_if:is_perishable,1', 'before_or_equal:' . today()->addYear()->toDateString()],
            'expiry_date' => ['nullable', 'date', 'required_if:is_perishable,1', 'after:entry_date', 'after_or_equal:today', 'before_or_equal:' . today()->addYears(5)->toDateString()],
        ]);

        $imagePath = $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null;
        DB::transaction(function () use ($request, $validated, $imagePath, $ledger, $activityLogger) {
            $product = PremiumProduct::create([
                'name' => $validated['name'],
                'item_code' => $validated['item_code'],
                'image_path' => $imagePath,
                'stock' => 0,
                'category' => $validated['category'],
                'is_perishable' => $request->boolean('is_perishable'),
                'entry_date' => $request->boolean('is_perishable') ? $request->input('entry_date') : null,
                'expiry_date' => $request->boolean('is_perishable') ? $request->input('expiry_date') : null,
            ]);

            $initialStock = (int) $validated['initial_stock'];
            $ledger->move($product, $initialStock, 'initial', $product, 'Initial stock entry.');
            $activityLogger->record('premium.created', 'Premium product ' . $product->name . ' created.', $product, ['initial_stock' => $initialStock]);
        });

        return redirect()->route('admin.dashboard')->with('success', 'Premium product added successfully!');
    }

    public function update(Request $request, string $id, PremiumStockLedger $ledger, ActivityLogger $activityLogger)
    {
        $product = PremiumProduct::findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', $this->uniqueProductNameRule($product)],
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

        DB::transaction(function () use ($request, $validated, $product, $imagePath, $ledger, $activityLogger) {
            $locked = PremiumProduct::query()->lockForUpdate()->findOrFail($product->id);
            $before = $locked->only(['name', 'category', 'is_perishable', 'entry_date', 'expiry_date', 'image_path', 'stock']);
            $newStock = (int) $validated['stock'];
            $locked->update([
                'name' => $validated['name'],
                'category' => $validated['category'],
                'image_path' => $imagePath,
                'is_perishable' => $request->boolean('is_perishable'),
                'entry_date' => $request->boolean('is_perishable') ? $request->input('entry_date') : null,
                'expiry_date' => $request->boolean('is_perishable') ? $request->input('expiry_date') : null,
            ]);

            $changes = [];
            foreach (['name', 'category', 'is_perishable', 'entry_date', 'expiry_date', 'image_path'] as $field) {
                $oldValue = $before[$field] instanceof \Carbon\CarbonInterface ? $before[$field]->toDateString() : $before[$field];
                $newValue = $locked->getAttribute($field) instanceof \Carbon\CarbonInterface ? $locked->getAttribute($field)->toDateString() : $locked->getAttribute($field);
                if ($oldValue != $newValue) {
                    $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
                }
            }
            if ($changes) {
                $activityLogger->record('premium.updated', 'Premium product ' . $locked->name . ' updated.', $locked, ['changes' => $changes]);
            }

            $oldStock = (int) $before['stock'];
            if ($newStock !== $oldStock) {
                $delta = $newStock - $oldStock;
                $ledger->move($locked, $delta, $delta > 0 ? 'restock' : 'adjustment', $locked, 'Admin stock edit: ' . $oldStock . ' to ' . $newStock . '.');
                $activityLogger->record('premium.stock_adjusted', 'Stock adjusted for ' . $locked->name . '.', $locked, ['old' => $oldStock, 'new' => $newStock, 'delta' => $delta]);
            }

            if ($locked->isExpired() && (int) $locked->stock > 0) {
                $expiredBalance = (int) $locked->stock;
                $ledger->move($locked, -$expiredBalance, 'expired_writeoff', $locked, 'Remaining on-hand stock written off after product expiry.');
            }
        });

        return redirect()->route('admin.dashboard')->with('success', 'Premium product updated successfully.');
    }

    public function destroy(string $id, ActivityLogger $activityLogger)
    {
        $product = PremiumProduct::withCount('promotions')->findOrFail($id);
        $hasActiveRewards = $product->earnedRewards()->whereIn('claim_status', ['unclaimed', 'claim_requested', 'released'])->exists();

        if ($product->promotions_count > 0 || $hasActiveRewards) {
            return back()->withErrors(['error' => 'This premium product cannot be deleted while a promotion or available reward uses it. Deactivate its promotion or resolve the reward first.']);
        }

        DB::transaction(function () use ($product, $activityLogger) {
            $snapshot = ['name' => $product->name, 'item_code' => $product->item_code, 'stock' => $product->stock];
            $product->delete();
            $activityLogger->record('premium.deleted', 'Premium product ' . $snapshot['name'] . ' deleted.', $product, $snapshot);
        });

        return back()->with('success', 'Premium product removed.');
    }

    private function uniqueProductNameRule(?PremiumProduct $currentProduct = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($currentProduct): void {
            $normalizedName = mb_strtolower(trim((string) $value));
            if ($currentProduct && $normalizedName === mb_strtolower(trim($currentProduct->name))) {
                return;
            }

            $query = PremiumProduct::query()->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName]);
            if ($currentProduct) {
                $query->where('id', '<>', $currentProduct->id);
            }

            if ($query->exists()) {
                $fail('A premium product with this name already exists. Add a distinguishing product name to avoid duplicates.');
            }
        };
    }
}
