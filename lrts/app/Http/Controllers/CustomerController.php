<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClaimService;
use App\Services\ReceiptSubmissionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['ticket' => 'nullable|integer|min:1']);
        /** @var User $user */
        $user = $request->user();
        $receipts = $user->receipts()
            ->with(['items', 'earnedRewards.premiumProduct', 'earnedRewards.releaser', 'items.inventory'])
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'receipts_page')->withQueryString();
        if ($request->header('X-RTS-Fragment') === 'lists') {
            return response(view('customer.partials.tracker', compact('receipts'))->render().view('customer.partials.order-details', compact('receipts'))->render());
        }
        $products = Inventory::orderBy('name')->get();
        $promotions = Promotion::with('premiumProduct')->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', today()))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
            ->get();
        $claimableReceipts = $user->receipts()->with(['items', 'earnedRewards.premiumProduct', 'earnedRewards.releaser'])
            ->where('status', 'approved')->whereHas('earnedRewards', fn ($q) => $q->whereIn('claim_status', ['unclaimed', 'claim_requested', 'released']))->latest()->paginate(10, ['*'], 'rewards_page')->withQueryString();
        $claimHistory = $user->earnedRewards()
            ->with(['premiumProduct', 'receipt'])
            ->whereIn('claim_status', ['claimed', 'voided', 'expired'])
            ->latest()
            ->paginate(10, ['*'], 'claims_page')->withQueryString();
        $pendingReceiptCount = $user->receipts()->where('status', 'pending')->count();
        $notifications = $user->customerNotifications()->latest('created_at')->limit(10)->get();
        $unreadNotificationCount = $user->customerNotifications()->whereNull('read_at')->count();
        $tickets = $user->tickets()
            ->withCount(['replies' => fn ($q) => $q->where('is_internal_note', false)])
            ->with(['replies' => fn ($query) => $query->where('is_internal_note', false)->with('user')->orderBy('created_at')])
            ->latest()
            ->paginate(10, ['*'], 'tickets_page')->withQueryString();
        if ($request->filled('ticket') && ! $tickets->getCollection()->contains('id', (int) $request->query('ticket'))) {
            $selected = $user->tickets()->withCount(['replies' => fn ($q) => $q->where('is_internal_note', false)])->with(['replies' => fn ($q) => $q->where('is_internal_note', false)->with('user')->orderBy('created_at')])->find($request->query('ticket'));
            if ($selected) {
                $tickets->setCollection($tickets->getCollection()->prepend($selected));
            }
        }

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
        Gate::authorize('update', $receipt);

        try {
            DB::transaction(function () use ($receipt, $request, $activityLogger) {
                $locked = Receipt::query()->lockForUpdate()->findOrFail($receipt->id);
                abort_unless($locked->user_id === $request->user()->id, 403);
                if ($locked->status !== 'pending') {
                    throw new RuntimeException('Only processing receipts can be cancelled.');
                }

                $locked->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                $activityLogger->record('receipt.cancelled', 'Customer cancelled order '.$locked->salesman_order_number.'.', $locked, [
                    'order_number' => $locked->salesman_order_number,
                ]);
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception instanceof QueryException ? 'The record changed or is busy. Refresh and try again.' : $exception->getMessage()]);
        }

        return redirect()->route('customer.dashboard', ['tab' => 'tracker'])->with('success', 'Receipt cancelled. Its order number is available again.');
    }

    public function claimReceipt(Request $request, Receipt $receipt, ClaimService $claims)
    {
        try {
            $code = $claims->requestClaim($receipt, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception instanceof QueryException ? 'The record changed or is busy. Refresh and try again.' : $exception->getMessage()]);
        }

        return redirect()->route('customer.dashboard', ['tab' => 'promos', 'section' => 'claims'])
            ->with('success', 'Your claim code is '.$code.'. Show it to staff to receive your rewards.');
    }

    public function confirmRewardsReceived(Request $request, Receipt $receipt, ClaimService $claims)
    {
        try {
            $rewards = $claims->confirmReceived($receipt, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception instanceof QueryException ? 'The record changed or is busy. Refresh and try again.' : $exception->getMessage()]);
        }

        return redirect()->route('customer.dashboard', ['tab' => 'promos', 'section' => 'claims'])
            ->with('success', 'Receipt confirmed for '.$rewards->sum('reward_quantity').' reward item(s). They are now in your claim history.');
    }

    public function markNotificationsRead(Request $request)
    {
        $request->validate(['ids' => 'sometimes|array|max:10', 'ids.*' => 'integer|min:1']);
        $latest = $request->has('ids') ? $request->input('ids') : $request->user()->customerNotifications()->latest('id')->limit(10)->pluck('id');
        $request->user()->customerNotifications()->whereIn('id', $latest)->whereNull('read_at')->update(['read_at' => now()]);
        Cache::forget('rts-pulse:customer:'.$request->user()->id);

        return response()->json(['ok' => true, 'unread_count' => $request->user()->customerNotifications()->whereNull('read_at')->count()]);
    }
}
