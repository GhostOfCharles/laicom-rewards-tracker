<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EarnedReward;
use App\Models\Promotion;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $validated = $request->validate([
            'buy_product_name' => 'required|string|exists:inventories,name',
            'required_quantity' => 'required|integer|min:1',
            'premium_product_id' => 'required|exists:premium_products,id',
            'reward_quantity' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'is_active' => 'required|boolean',
        ]);

        $title = $validated['title'] ?? "BUY {$validated['required_quantity']} {$validated['buy_product_name']} GET {$validated['reward_quantity']}";
        DB::transaction(function () use ($validated, $title, $activityLogger) {
            $promotion = Promotion::create([...$validated, 'title' => $title, 'is_active' => (bool) $validated['is_active']]);
            $activityLogger->record('promotion.created', 'Promotion ' . $promotion->title . ' created.', $promotion, $promotion->only(['title', 'buy_product_name', 'required_quantity', 'premium_product_id', 'reward_quantity', 'start_date', 'end_date', 'is_active']));
            if ($promotion->is_active) {
                $activityLogger->record('promotion.activated', 'Promotion ' . $promotion->title . ' activated.', $promotion, ['start_date' => $promotion->start_date?->toDateString(), 'end_date' => $promotion->end_date?->toDateString()]);
            }
        });

        return redirect()->back()->with('success', 'Promotion created successfully.');
    }

    public function update(Request $request, string $id, ActivityLogger $activityLogger)
    {
        $promo = Promotion::findOrFail($id);
        $validated = $request->validate([
            'buy_product_name' => 'required|string|exists:inventories,name',
            'required_quantity' => 'required|integer|min:1',
            'premium_product_id' => 'required|exists:premium_products,id',
            'reward_quantity' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'is_active' => 'required|boolean',
        ]);
        $validated['title'] = $validated['title'] ?: "BUY {$validated['required_quantity']} {$validated['buy_product_name']} GET {$validated['reward_quantity']}";
        $validated['is_active'] = (bool) $validated['is_active'];

        DB::transaction(function () use ($promo, $validated, $activityLogger) {
            $locked = Promotion::query()->lockForUpdate()->findOrFail($promo->id);
            $old = $locked->only(array_keys($validated));
            $locked->update($validated);
            $changes = [];
            foreach ($validated as $field => $newValue) {
                if ((string) $old[$field] !== (string) $newValue) {
                    $changes[$field] = ['old' => $old[$field], 'new' => $newValue];
                }
            }
            if ($changes) {
                $activityLogger->record('promotion.updated', 'Promotion ' . $locked->title . ' updated.', $locked, ['changes' => $changes]);
            }
            if ((bool) $old['is_active'] !== (bool) $validated['is_active']) {
                $action = $validated['is_active'] ? 'promotion.activated' : 'promotion.deactivated';
                $activityLogger->record($action, 'Promotion ' . $locked->title . ($validated['is_active'] ? ' activated.' : ' deactivated.'), $locked, ['old' => (bool) $old['is_active'], 'new' => (bool) $validated['is_active']]);
            }
        });

        return redirect()->back()->with('success', 'Promotion updated successfully.');
    }

    public function destroy(string $id, ActivityLogger $activityLogger)
    {
        $promotion = Promotion::findOrFail($id);
        if (EarnedReward::where('promotion_id', $promotion->id)->exists()) {
            return back()->withErrors(['error' => 'This promotion has reward history and cannot be deleted. Deactivate it instead.']);
        }

        DB::transaction(function () use ($promotion, $activityLogger) {
            $properties = $promotion->only(['title', 'buy_product_name', 'required_quantity', 'premium_product_id', 'reward_quantity', 'start_date', 'end_date', 'is_active']);
            $title = $promotion->title;
            $promotion->delete();
            $activityLogger->record('promotion.deleted', 'Promotion ' . $title . ' deleted.', $promotion, $properties);
        });

        return redirect()->back()->with('success', 'Promotion removed.');
    }
}
