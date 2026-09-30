<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Inventory;
use App\Models\Receipt;
use App\Models\Promotion;
use App\Models\EarnedReward;

class CustomerController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Fetch Receipts for the Tracker
        $receipts = $user->receipts()
            ->with(['items', 'earnedRewards.premiumProduct'])
            ->latest()
            ->get();

        // 2. Fetch Products for the Order Form Combo Box
        $products = Inventory::orderBy('name')->get();

        // 3. Fetch Active Promotions for the Promos tab
        $promotions = Promotion::with('premiumProduct')->where('is_active', 1)->get();

        // 4. Fetch Unclaimed Rewards specifically for this user
        $availableRewards = EarnedReward::with('premiumProduct')
            ->where('user_id', $user->id)
            ->where('claim_status', 'unclaimed')
            ->get();

        $pendingReceiptCount = $receipts->where('status', 'pending')->count();

        // 5. Fetch Tickets for the Help drawer
        $tickets = $user->tickets()
            ->withCount('replies')
            ->with(['replies' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->get();

        return view('customer.dashboard', compact(
            'receipts', 'products', 'promotions',
            'availableRewards', 'pendingReceiptCount', 'tickets'
        ));
    }

    public function submitOrder(Request $request)
    {
        $request->validate([
            'salesman_order_number' => 'required|string|unique:receipts,salesman_order_number',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'items.required' => 'You must add at least one product to your item listbox.',
        ]);

        $receipt = Receipt::create([
            'user_id' => Auth::id(),
            'salesman_order_number' => $request->salesman_order_number,
            'status' => 'pending',
        ]);

        foreach ($request->items as $item) {
            $receipt->items()->create([
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => 0,
            ]);
        }

        return redirect()->route('customer.dashboard')->with('success', 'Order submitted successfully! It is now pending admin review.');
    }

    public function claimReward(EarnedReward $reward)
    {
        abort_unless($reward->user_id === Auth::id(), 403);

        if ($reward->claim_status !== 'unclaimed') {
            return back()->withErrors(['error' => 'This reward is no longer available to claim.']);
        }

        $reward->update([
            'claim_status' => 'claimed',
            'claimed_at' => now(),
        ]);

        return back()->with('success', 'Reward marked as claimed. Please keep this record for your reference.');
    }
}