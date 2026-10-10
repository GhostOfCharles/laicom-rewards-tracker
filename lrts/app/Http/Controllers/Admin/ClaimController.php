<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerNotification;
use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Services\ActivityLogger;
use App\Services\ClaimService;
use App\Services\PremiumStockLedger;
use App\Services\RewardCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ClaimController extends Controller
{
    public function index(Request $request, RewardCalculator $calculator)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,approved,rejected,cancelled',
            'search' => 'nullable|string|max:100',
            'claim_filter' => 'nullable|in:all,awaiting_release,claimed,none_claimed',
        ]);

        $query = Receipt::with(['user', 'items', 'earnedRewards.premiumProduct', 'earnedRewards.releaser', 'activityLogs.user'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when(trim($validated['search'] ?? ''), function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('salesman_order_number', 'like', '%' . $term . '%')
                        ->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%' . $term . '%'));
                });
            });

        $claimFilter = $validated['claim_filter'] ?? 'all';
        $query->when($claimFilter === 'awaiting_release', fn ($query) => $query->whereHas('earnedRewards', fn ($rewards) => $rewards->where('claim_status', 'claim_requested')))
            ->when($claimFilter === 'awaiting_customer', fn ($query) => $query->whereHas('earnedRewards', fn ($rewards) => $rewards->where('claim_status', 'released')))
            ->when($claimFilter === 'claimed', fn ($query) => $query->whereHas('earnedRewards', fn ($rewards) => $rewards->where('claim_status', 'claimed')))
            ->when($claimFilter === 'none_claimed', fn ($query) => $query->whereDoesntHave('earnedRewards', fn ($rewards) => $rewards->where('claim_status', 'claimed')));

        $receipts = $query->latest('submitted_at')->paginate(25)->withQueryString();
        $promotions = Promotion::with('premiumProduct')->where('is_active', true)->get();
        $receiptProductImages = Inventory::query()->get(['name', 'image_path'])->keyBy('name');

        foreach ($receipts as $receipt) {
            $receipt->calculated_reward_groups = $receipt->status === 'pending' ? $calculator->forReceipt($receipt) : [];
            $receipt->has_duplicate_slip = $receipt->slip_hash && Receipt::where('slip_hash', $receipt->slip_hash)->whereKeyNot($receipt->id)->exists();
        }

        return view('admin.receipts', compact('receipts', 'promotions', 'claimFilter', 'receiptProductImages'));
    }

    public function approve(Request $request, int $id, RewardCalculator $calculator, PremiumStockLedger $ledger, ActivityLogger $activityLogger)
    {
        $admin = $request->user();

        try {
            DB::transaction(function () use ($request, $id, $calculator, $ledger, $activityLogger, $admin) {
                $receipt = Receipt::with('items')->lockForUpdate()->findOrFail($id);
                if ($receipt->status !== 'pending') {
                    throw ValidationException::withMessages(['error' => 'Only processing receipts can be approved.']);
                }

                $groups = $calculator->forReceipt($receipt);
                $selected = [];
                $reservedByProduct = [];
                foreach ($groups as $group) {
                    $promotionId = count($group['options']) === 1
                        ? $group['options'][0]['promotion_id']
                        : $request->input('selections.' . $group['buy_product_name']);

                    if (! $promotionId) {
                        throw ValidationException::withMessages(['error' => 'Choose one reward for ' . $group['buy_product_name'] . ' before approval.']);
                    }

                    $option = collect($group['options'])->first(fn ($option) => (string) $option['promotion_id'] === (string) $promotionId);
                    if (! $option) {
                        throw ValidationException::withMessages(['error' => 'The selected reward is not valid for ' . $group['buy_product_name'] . '. Refresh the page and choose a listed reward.']);
                    }

                    $product = PremiumProduct::query()->lockForUpdate()->find($option['premium_product_id']);
                    $reservedByProduct[$option['premium_product_id']] = ($reservedByProduct[$option['premium_product_id']] ?? 0) + $option['reward_quantity'];
                    if (! $product) {
                        throw ValidationException::withMessages(['error' => 'Insufficient stock for ' . ($product?->name ?? $option['premium_name']) . '. Choose another available reward if one is listed.']);
                    }
                    if ($product->isExpired()) {
                        throw ValidationException::withMessages(['error' => 'Cannot approve this reward selection because ' . $product->name . ' has expired. Choose another listed reward.']);
                    }

                    $selected[] = ['group' => $group, 'option' => $option, 'product' => $product];
                }

                foreach ($reservedByProduct as $productId => $requiredStock) {
                    $product = PremiumProduct::query()->lockForUpdate()->find($productId);
                    if (! $product || $product->stock < $requiredStock) {
                        throw ValidationException::withMessages(['error' => 'Insufficient stock for ' . ($product?->name ?? 'a selected reward') . '. Choose another available reward if one is listed.']);
                    }
                }

                $selectionLog = [];
                foreach ($selected as $selection) {
                    $group = $selection['group'];
                    $option = $selection['option'];
                    $product = $selection['product'];
                    $ledger->move($product, -$option['reward_quantity'], 'reward_reserved', $receipt, 'Reward stock reserved when order approved.');
                    EarnedReward::create([
                        'user_id' => $receipt->user_id,
                        'receipt_id' => $receipt->id,
                        'promotion_id' => $option['promotion_id'],
                        'premium_product_id' => $option['premium_product_id'],
                        'reward_quantity' => $option['reward_quantity'],
                        'claim_status' => 'unclaimed',
                        'expires_at' => now()->addDays((int) config('lrts.claim_window_days', 30)),
                        'selected_by' => $admin->id,
                        'promotion_title' => $option['promotion_title'],
                        'reward_product_name' => $option['premium_name'],
                        'buy_product_name' => $group['buy_product_name'],
                        'purchased_quantity' => $group['purchased_quantity'],
                        'promo_required_quantity' => $option['required_quantity'],
                        'promo_reward_quantity' => $option['promo_reward_quantity'],
                    ]);
                    $selectionLog[] = [
                        'buy_product_name' => $group['buy_product_name'],
                        'promotion_id' => $option['promotion_id'],
                        'premium_product_id' => $option['premium_product_id'],
                        'quantity' => $option['reward_quantity'],
                    ];
                }

                $receipt->update(['status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
                $activityLogger->record('receipt.approved', 'Receipt ' . $receipt->salesman_order_number . ' approved.', $receipt, [
                    'selections' => $selectionLog,
                    'stock_moved' => array_map(fn ($selection) => ['premium_product_id' => $selection['premium_product_id'], 'quantity' => $selection['quantity']], $selectionLog),
                ]);
                CustomerNotification::create([
                    'user_id' => $receipt->user_id,
                    'type' => 'receipt.approved',
                    'title' => 'Receipt approved',
                    'body' => 'Your order ' . $receipt->salesman_order_number . ' was approved. Your rewards are ready to request.',
                    'receipt_id' => $receipt->id,
                    'created_at' => now(),
                ]);
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        return back()->with('success', 'Receipt approved and reward stock reserved.');
    }

    public function reject(Request $request, int $id, ActivityLogger $activityLogger)
    {
        $validated = $request->validate(['reason' => 'required|string|min:5|max:500']);
        $admin = $request->user();

        try {
            DB::transaction(function () use ($id, $validated, $admin, $activityLogger) {
                $receipt = Receipt::query()->lockForUpdate()->findOrFail($id);
                if ($receipt->status !== 'pending') {
                    throw ValidationException::withMessages(['error' => 'Only processing receipts can be rejected.']);
                }

                $receipt->update(['status' => 'rejected', 'rejection_reason' => $validated['reason'], 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
                $activityLogger->record('receipt.rejected', 'Receipt ' . $receipt->salesman_order_number . ' rejected.', $receipt, ['reason' => $validated['reason']]);
                CustomerNotification::create([
                    'user_id' => $receipt->user_id,
                    'type' => 'receipt.rejected',
                    'title' => 'Receipt needs attention',
                    'body' => 'Your order ' . $receipt->salesman_order_number . ' was rejected: ' . $validated['reason'],
                    'receipt_id' => $receipt->id,
                    'created_at' => now(),
                ]);
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        return back()->with('success', 'Receipt rejected. The reason has been sent to the customer.');
    }

    public function release(Request $request, Receipt $receipt, ClaimService $claims)
    {
        Gate::authorize('release-rewards');
        $validated = $request->validate(['release_note' => 'nullable|string|max:255']);
        try {
            $claims->release($receipt, $request->user(), $validated['release_note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
        return back()->with('success', 'Reward release confirmed.');
    }

    public function releaseReward(Request $request, EarnedReward $reward, ClaimService $claims)
    {
        Gate::authorize('release-rewards');
        $validated = $request->validate(['release_note' => 'nullable|string|max:255']);
        try {
            $claims->release($reward, $request->user(), $validated['release_note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
        return back()->with('success', 'Reward release confirmed.');
    }

    public function voidReward(Request $request, EarnedReward $reward, ClaimService $claims)
    {
        Gate::authorize('release-rewards');
        $validated = $request->validate(['reason' => 'required|string|min:5|max:255', 'return_stock' => 'nullable|boolean']);
        try {
            $claims->void($reward, $request->user(), $validated['reason'], $request->boolean('return_stock', true));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
        return back()->with('success', 'Reward voided.');
    }
}
