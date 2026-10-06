<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClaimService;
use App\Services\ReceiptSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $receipts = $user->receipts()
            ->with(['items', 'earnedRewards.premiumProduct', 'earnedRewards.releaser', 'activityLogs.user'])
            ->latest('submitted_at')
            ->get();
        $products = Inventory::orderBy('name')->get();
        $promotions = Promotion::with('premiumProduct')->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', today()))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
            ->get();
        $claimableReceipts = $receipts->filter(fn (Receipt $receipt) => $receipt->status === 'approved'
            && $receipt->earnedRewards->contains(fn ($reward) => in_array($reward->claim_status, ['unclaimed', 'claim_requested'], true)));
        $claimHistory = $user->earnedRewards()
            ->with(['premiumProduct', 'receipt'])
            ->whereIn('claim_status', ['claimed', 'voided', 'expired'])
            ->latest()
            ->get();
        $pendingReceiptCount = $receipts->where('status', 'pending')->count();
        $notifications = $user->customerNotifications()->latest('created_at')->limit(10)->get();
        $unreadNotificationCount = $user->customerNotifications()->whereNull('read_at')->count();
        $tickets = $user->tickets()
            ->withCount('replies')
            ->with(['replies' => fn ($query) => $query->where('is_internal_note', false)->with('user')->orderBy('created_at')])
            ->latest()
            ->get();

        return view('customer.dashboard', compact(
            'receipts', 'products', 'promotions', 'claimableReceipts', 'claimHistory',
            'pendingReceiptCount', 'notifications', 'unreadNotificationCount', 'tickets'
        ));
    }

    public function submitOrder(Request $request, ReceiptSubmissionService $submissions)
    {
        $submissions->createFromRequest($request, $request->user());

        return redirect()->route('customer.dashboard')->with('success', 'Order submitted successfully! It is now pending admin review.');
    }

    public function cancelReceipt(Request $request, Receipt $receipt, ActivityLogger $activityLogger)
    {
        abort_unless($receipt->user_id === $request->user()->id, 403);

        try {
            DB::transaction(function () use ($receipt, $request, $activityLogger) {
                $locked = Receipt::query()->lockForUpdate()->findOrFail($receipt->id);
                abort_unless($locked->user_id === $request->user()->id, 403);
                if ($locked->status !== 'pending') {
                    throw new RuntimeException('Only processing receipts can be cancelled.');
                }

                $locked->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                $activityLogger->record('receipt.cancelled', 'Customer cancelled order ' . $locked->salesman_order_number . '.', $locked, [
                    'order_number' => $locked->salesman_order_number,
                ]);
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        return redirect()->route('customer.dashboard', ['tab' => 'tracker'])->with('success', 'Receipt cancelled. Its order number is available again.');
    }

    public function claimReceipt(Request $request, Receipt $receipt, ClaimService $claims)
    {
        try {
            $code = $claims->requestClaim($receipt, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        return redirect()->route('customer.dashboard', ['tab' => 'promos', 'section' => 'claims'])
            ->with('success', 'Your claim code is ' . $code . '. Show it to Laicom staff to receive your rewards.');
    }

    public function orderSlip(Request $request, Receipt $receipt)
    {
        abort_unless($receipt->user_id === $request->user()->id, 403);
        return $this->privateFile($receipt->slip_path);
    }

    public function markNotificationsRead(Request $request)
    {
        $request->user()->customerNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

}
