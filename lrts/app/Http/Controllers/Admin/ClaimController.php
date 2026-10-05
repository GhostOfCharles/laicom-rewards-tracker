<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Promotion;
use App\Models\PremiumProduct;
use App\Models\EarnedReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $receipts = Receipt::with(['user', 'items', 'earnedRewards.premiumProduct'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->get();
        $promotions = Promotion::with('premiumProduct')->where('is_active', 1)->get(); 

        foreach ($receipts as $receipt) {
            $rewards = [];
            foreach ($receipt->items->groupBy('product_name') as $productName => $items) {
                $purchaseQuantity = $items->sum('quantity');
                $matchingPromos = $promotions->where('buy_product_name', $productName);
                
                foreach ($matchingPromos as $promo) {
                    $multiplier = intdiv($purchaseQuantity, $promo->required_quantity);
                    if ($multiplier > 0) {
                        $earnedQty = $multiplier * $promo->reward_quantity;
                        $pid = $promo->premium_product_id;
                        
                        if (!isset($rewards[$pid])) {
                            $rewards[$pid] = [
                                'promotion_id' => $promo->id,
                                'premium_product_id' => $pid,
                                'name' => $promo->premiumProduct->name ?? 'Unknown Premium',
                                'quantity' => 0
                            ];
                        }
                        $rewards[$pid]['quantity'] += $earnedQty;
                    }
                }
            }
            $receipt->calculated_rewards = $rewards;
        }

        return view('admin.receipts', compact('receipts'));
    }

    public function approve($id)
    {
        try {
            $hasExpiredReward = DB::transaction(function () use ($id) {
                $receipt = Receipt::with('items')->lockForUpdate()->findOrFail($id);

                if ($receipt->status !== 'pending') {
                    throw new \RuntimeException('Only processing receipts can be approved.');
                }

                $pendingRewards = $this->calculateRewards($receipt);
                $stockCheck = [];
                foreach ($pendingRewards as $reward) {
                    $stockCheck[$reward['premium_product_id']] = ($stockCheck[$reward['premium_product_id']] ?? 0) + $reward['reward_quantity'];
                }

                $premiumProducts = PremiumProduct::whereIn('id', array_keys($stockCheck))->lockForUpdate()->get()->keyBy('id');
                foreach ($stockCheck as $productId => $quantity) {
                    $product = $premiumProducts->get($productId);
                    if (! $product || $product->stock < $quantity) {
                        throw new \RuntimeException('Insufficient stock for ' . ($product->name ?? 'a premium product') . '.');
                    }
                }

                // Approval continues even when a linked perishable reward product has expired.
                $hasExpiredReward = $premiumProducts->contains(fn (PremiumProduct $product) => $product->isExpired());

                foreach ($pendingRewards as $reward) {
                    $premiumProducts->get($reward['premium_product_id'])->decrement('stock', $reward['reward_quantity']);
                    EarnedReward::create([
                        'user_id' => $receipt->user_id,
                        'receipt_id' => $receipt->id,
                        'promotion_id' => $reward['promotion_id'],
                        'premium_product_id' => $reward['premium_product_id'],
                        'reward_quantity' => $reward['reward_quantity'],
                        'claim_status' => 'unclaimed',
                    ]);
                }

                $receipt->update(['status' => 'approved']);

                return $hasExpiredReward;
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        $message = 'Receipt approved successfully. Premium inventory has been deducted.';
        if ($hasExpiredReward) {
            $message .= ' Warning: one or more linked perishable premium products have expired.';
        }

        return back()->with('success', $message);
    }

    public function reject($id)
    {
        $wasRejected = DB::transaction(function () use ($id) {
            $receipt = Receipt::lockForUpdate()->findOrFail($id);
            if ($receipt->status !== 'pending') {
                return false;
            }

            $receipt->update(['status' => 'rejected']);
            return true;
        });

        if (! $wasRejected) {
            return back()->withErrors(['error' => 'Only processing receipts can be rejected.']);
        }

        return back()->with('success', 'Receipt has been rejected.');
    }

    private function calculateRewards(Receipt $receipt): array
    {
        $promotions = Promotion::where('is_active', true)->get()->groupBy('buy_product_name');
        $rewards = [];

        foreach ($receipt->items->groupBy('product_name') as $productName => $items) {
            $purchaseQuantity = $items->sum('quantity');
            foreach ($promotions->get($productName, collect()) as $promo) {
                $quantity = intdiv($purchaseQuantity, $promo->required_quantity) * $promo->reward_quantity;
                if ($quantity > 0) {
                    $rewards[] = [
                        'promotion_id' => $promo->id,
                        'premium_product_id' => $promo->premium_product_id,
                        'reward_quantity' => $quantity,
                    ];
                }
            }
        }

        return $rewards;
    }
}
