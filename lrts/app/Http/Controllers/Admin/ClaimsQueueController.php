<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EarnedReward;
use App\Models\Receipt;
use App\Services\ClaimService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ClaimsQueueController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'tab' => 'nullable|in:awaiting,available,released,voided_expired',
            'code' => 'nullable|string|max:20',
        ]);
        $tab = $validated['tab'] ?? 'awaiting';
        $code = mb_strtoupper(trim($validated['code'] ?? ''));
        $statusMap = [
            'awaiting' => ['claim_requested'],
            'available' => ['unclaimed'],
            'released' => ['claimed'],
            'voided_expired' => ['voided', 'expired'],
        ];

        $query = EarnedReward::with(['premiumProduct', 'user', 'receipt.items', 'releaser'])
            ->whereIn('claim_status', $statusMap[$tab]);
        if ($code !== '') {
            $query->where('claim_code', $code);
        }
        if ($tab === 'available') {
            $query->orderByRaw('expires_at IS NULL')->orderBy('expires_at');
        } else {
            $query->latest();
        }

        $groups = $query->get()->groupBy(fn (EarnedReward $reward) => $reward->receipt_id . ':' . ($reward->claim_code ?? 'walk-in'));
        $counts = [
            'awaiting' => EarnedReward::where('claim_status', 'claim_requested')->distinct('receipt_id')->count('receipt_id'),
            'available' => EarnedReward::where('claim_status', 'unclaimed')->distinct('receipt_id')->count('receipt_id'),
            'released' => EarnedReward::where('claim_status', 'claimed')->distinct('receipt_id')->count('receipt_id'),
            'voided_expired' => EarnedReward::whereIn('claim_status', ['voided', 'expired'])->distinct('receipt_id')->count('receipt_id'),
        ];

        return view('admin.claims', compact('groups', 'tab', 'code', 'counts'));
    }

    public function release(Request $request, string $code, ClaimService $claims)
    {
        Gate::authorize('release-rewards');
        $validated = $request->validate(['release_note' => 'nullable|string|max:255']);
        try {
            $claims->release($code, $request->user(), $validated['release_note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
        return back()->with('success', 'Claim release confirmed.');
    }

    public function releaseReceipt(Request $request, Receipt $receipt, ClaimService $claims)
    {
        Gate::authorize('release-rewards');
        $validated = $request->validate(['release_note' => 'nullable|string|max:255']);
        try {
            $claims->release($receipt, $request->user(), $validated['release_note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
        return back()->with('success', 'Walk-in release confirmed.');
    }
}
