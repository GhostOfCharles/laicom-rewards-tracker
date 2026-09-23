<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Promotion;
use App\Models\PremiumProduct;
use App\Models\EarnedReward;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function index()
    {
        $receipts = Receipt::with(['user', 'items'])->orderBy('created_at', 'desc')->get();
        $promotions = Promotion::with('premiumProduct')->where('is_active', 1)->get(); 

        foreach ($receipts as $receipt) {
            $rewards = [];
            foreach ($receipt->items as $item) {
                $matchingPromos = $promotions->where('buy_product_name', $item->product_name);
                
                foreach ($matchingPromos as $promo) {
                    $multiplier = floor($item->quantity / $promo->required_quantity);
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
        $receipt = Receipt::with('items')->findOrFail($id);
        $promotions = Promotion::where('is_active', 1)->get();
        
        $pendingRewards = [];
        $stockCheck = [];

        // 1. Calculate everything earned on this receipt
        foreach ($receipt->items as $item) {
            $matchingPromos = $promotions->where('buy_product_name', $item->product_name);
            foreach ($matchingPromos as $promo) {
                $multiplier = floor($item->quantity / $promo->required_quantity);
                if ($multiplier > 0) {
                    $earnedQty = $multiplier * $promo->reward_quantity;
                    $pid = $promo->premium_product_id;
                    
                    $stockCheck[$pid] = ($stockCheck[$pid] ?? 0) + $earnedQty;
                    $pendingRewards[] = [
                        'promotion_id' => $promo->id,
                        'premium_product_id' => $pid,
                        'reward_quantity' => $earnedQty
                    ];
                }
            }
        }

        // 2. Prevent Silent Failures: Verify absolute total stock availability before any deductions
        foreach ($stockCheck as $pid => $totalNeeded) {
            $premium = PremiumProduct::find($pid);
            if (!$premium || $premium->stock < $totalNeeded) {
                return redirect()->back()->withErrors(['error' => 'Insufficient stock for ' . ($premium->name ?? 'Premium Product') . '. Needed: ' . $totalNeeded . ' | Available: ' . ($premium->stock ?? 0)]);
            }
        }

        // 3. Stock is guaranteed. Deduct inventory and write to EarnedRewards.
        foreach ($pendingRewards as $reward) {
            PremiumProduct::where('id', $reward['premium_product_id'])->decrement('stock', $reward['reward_quantity']);
            
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
        return redirect()->back()->with('success', 'Receipt approved successfully! Premium inventory has been deducted.');
    }

    public function reject($id)
    {
        Receipt::findOrFail($id)->update(['status' => 'rejected']);
        return redirect()->back()->with('success', 'Receipt has been rejected.');
    }
}