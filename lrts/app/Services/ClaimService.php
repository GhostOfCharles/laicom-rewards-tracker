<?php

namespace App\Services;

use App\Models\CustomerNotification;
use App\Models\EarnedReward;
use App\Models\PremiumProduct;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ClaimService
{
    public function __construct(
        private readonly PremiumStockLedger $stockLedger,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function requestClaim(Receipt $receipt, User $user): string
    {
        return DB::transaction(function () use ($receipt, $user) {
            $lockedReceipt = Receipt::query()->lockForUpdate()->findOrFail($receipt->id);
            if ($lockedReceipt->user_id !== $user->id) {
                abort(403);
            }
            if ($lockedReceipt->status !== 'approved') {
                throw new RuntimeException('Rewards can only be requested for an approved receipt.');
            }

            $rewards = EarnedReward::query()
                ->where('receipt_id', $lockedReceipt->id)
                ->whereIn('claim_status', ['unclaimed', 'claim_requested'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($rewards->isEmpty()) {
                throw new RuntimeException('There are no available rewards to request for this receipt.');
            }

            $existingCode = $rewards->first(fn (EarnedReward $reward) => $reward->claim_status === 'claim_requested')?->claim_code;

            foreach ($rewards as $reward) {
                if ($reward->claim_status !== 'unclaimed') {
                    continue;
                }
                $product = $reward->premium_product_id ? PremiumProduct::query()->find($reward->premium_product_id) : null;
                if ($product?->isExpired()) {
                    throw new RuntimeException('This reward is no longer available because the linked premium product has expired. Please contact support.');
                }
                if ($reward->expires_at && $reward->expires_at->isPast()) {
                    throw new RuntimeException('This reward has expired. Please contact support.');
                }
            }

            $claimCode = $existingCode ?: $this->newClaimCode();
            $newlyRequested = false;
            foreach ($rewards as $reward) {
                if ($reward->claim_status === 'claim_requested') {
                    continue;
                }
                $reward->update([
                    'claim_status' => 'claim_requested',
                    'claim_code' => $claimCode,
                    'claim_requested_at' => now(),
                ]);
                $newlyRequested = true;
            }

            if ($newlyRequested) {
                $this->activityLogger->record('reward.claim_requested', 'Customer requested rewards for order ' . $lockedReceipt->salesman_order_number . '.', $lockedReceipt, [
                    'claim_code' => $claimCode,
                    'reward_ids' => $rewards->modelKeys(),
                ]);
                $this->notify($user->id, 'reward.claim_requested', 'Claim code ready', 'Your claim code for order ' . $lockedReceipt->salesman_order_number . ' is ' . $claimCode . '. Show it to Laicom staff when collecting your rewards.', $lockedReceipt->id);
            }

            return $claimCode;
        });
    }

    public function release(string|Receipt|EarnedReward $target, User $admin, ?string $note = null)
    {
        if ($admin->role !== 'admin') {
            abort(403);
        }

        return DB::transaction(function () use ($target, $admin, $note) {
            if (is_string($target)) {
                $query = EarnedReward::query()->where('claim_code', mb_strtoupper(trim($target)));
            } elseif ($target instanceof Receipt) {
                $query = EarnedReward::query()->where('receipt_id', $target->id)->whereIn('claim_status', ['claim_requested', 'unclaimed']);
            } else {
                $query = EarnedReward::query()->whereKey($target->id);
            }

            $rewards = $query->orderBy('id')->lockForUpdate()->get();
            if ($rewards->isEmpty()) {
                throw new RuntimeException('No releasable rewards were found for that claim.');
            }

            $receipt = Receipt::query()->lockForUpdate()->findOrFail($rewards->first()->receipt_id);
            if ($receipt->status !== 'approved') {
                throw new RuntimeException('Rewards can only be released for an approved receipt.');
            }

            foreach ($rewards as $reward) {
                if (! in_array($reward->claim_status, ['unclaimed', 'claim_requested'], true)) {
                    throw new RuntimeException('One or more rewards have already been released or are no longer available.');
                }
                $product = $reward->premium_product_id ? PremiumProduct::query()->lockForUpdate()->find($reward->premium_product_id) : null;
                if ($product?->isExpired()) {
                    throw new RuntimeException('Release blocked: the linked premium reward product has expired.');
                }
                if ($reward->expires_at && $reward->expires_at->isPast()) {
                    throw new RuntimeException('Release blocked: one or more rewards have expired.');
                }
            }

            foreach ($rewards as $reward) {
                $reward->update([
                    'claim_status' => 'claimed',
                    'claimed_at' => now(),
                    'released_by' => $admin->id,
                    'release_note' => $note ? mb_substr($note, 0, 255) : null,
                ]);
            }

            $this->activityLogger->record('reward.released', 'Staff released ' . $rewards->count() . ' reward type(s) for order ' . $receipt->salesman_order_number . '.', $receipt, [
                'claim_code' => $rewards->first()->claim_code,
                'reward_ids' => $rewards->modelKeys(),
                'release_note' => $note,
            ]);
            $this->notify($receipt->user_id, 'reward.released', 'Rewards released', 'Your rewards for order ' . $receipt->salesman_order_number . ' have been released.', $receipt->id);

            return $rewards;
        });
    }

    public function void(EarnedReward $reward, User $admin, string $reason, bool $returnStock = true): EarnedReward
    {
        return DB::transaction(function () use ($reward, $admin, $reason, $returnStock) {
            $locked = EarnedReward::query()->lockForUpdate()->findOrFail($reward->id);
            if (! in_array($locked->claim_status, ['unclaimed', 'claim_requested'], true)) {
                throw new RuntimeException('Only available or requested rewards can be voided.');
            }

            $product = $locked->premium_product_id ? PremiumProduct::query()->lockForUpdate()->find($locked->premium_product_id) : null;
            if ($product?->isExpired()) {
                $this->stockLedger->move($product, 0, 'expired_writeoff', $locked, 'Reward voided after the linked premium product expired.');
            } elseif ($returnStock && $product) {
                $this->stockLedger->move($product, (int) $locked->reward_quantity, 'reward_returned', $locked, 'Reward voided: ' . $reason);
            }

            $locked->update([
                'claim_status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $admin->id,
                'void_reason' => mb_substr($reason, 0, 255),
            ]);
            $receipt = Receipt::find($locked->receipt_id);
            $this->activityLogger->record('reward.voided', 'Reward voided for order ' . ($receipt?->salesman_order_number ?? 'unknown') . '.', $locked, [
                'reason' => $reason,
                'stock_returned' => (bool) ($returnStock && ! $product?->isExpired() && $product),
                'reward_quantity' => $locked->reward_quantity,
            ]);
            if ($receipt) {
                $this->notify($locked->user_id, 'reward.voided', 'Reward voided', 'A reward for order ' . $receipt->salesman_order_number . ' was voided: ' . $reason, $receipt->id);
            }

            return $locked;
        });
    }

    public function expire(EarnedReward $reward): bool
    {
        return DB::transaction(function () use ($reward) {
            $locked = EarnedReward::query()->lockForUpdate()->findOrFail($reward->id);
            if (! in_array($locked->claim_status, ['unclaimed', 'claim_requested'], true)
                || ! $locked->expires_at
                || $locked->expires_at->isFuture()) {
                return false;
            }

            $product = $locked->premium_product_id ? PremiumProduct::query()->lockForUpdate()->find($locked->premium_product_id) : null;
            if ($product?->isExpired()) {
                $this->stockLedger->move($product, 0, 'expired_writeoff', $locked, 'Reward expired while its linked premium product was expired.');
            } elseif ($product) {
                $this->stockLedger->move($product, (int) $locked->reward_quantity, 'reward_returned', $locked, 'Claim window expired.');
            }

            $locked->update(['claim_status' => 'expired', 'expired_at' => now(), 'void_reason' => 'Claim window expired.']);
            $receipt = Receipt::find($locked->receipt_id);
            $this->activityLogger->record('reward.expired', 'Reward claim window expired.', $locked, [
                'receipt_id' => $locked->receipt_id,
                'claim_code' => $locked->claim_code,
                'stock_returned' => (bool) ($product && ! $product->isExpired()),
            ]);
            if ($receipt) {
                $this->notify($locked->user_id, 'reward.expired', 'Reward expired', 'A reward for order ' . $receipt->salesman_order_number . ' has expired.', $receipt->id);
            }

            return true;
        });
    }

    public function expireForProduct(EarnedReward $reward): bool
    {
        return DB::transaction(function () use ($reward) {
            $locked = EarnedReward::query()->lockForUpdate()->findOrFail($reward->id);
            if (! in_array($locked->claim_status, ['unclaimed', 'claim_requested'], true)) {
                return false;
            }

            $product = $locked->premium_product_id ? PremiumProduct::query()->lockForUpdate()->find($locked->premium_product_id) : null;
            if (! $product?->isExpired()) {
                return false;
            }

            $this->stockLedger->move($product, 0, 'expired_writeoff', $locked, 'Reserved reward marked expired after its premium product expiry date.');
            $locked->update(['claim_status' => 'expired', 'expired_at' => now(), 'void_reason' => 'The linked premium reward product expired.']);
            $receipt = Receipt::find($locked->receipt_id);
            $this->activityLogger->record('reward.expired', 'Reward expired because its premium product expired.', $locked, [
                'receipt_id' => $locked->receipt_id,
                'claim_code' => $locked->claim_code,
                'premium_product_id' => $product->id,
                'stock_returned' => false,
            ]);
            if ($receipt) {
                $this->notify($locked->user_id, 'reward.expired', 'Reward no longer available', 'A reward for order ' . $receipt->salesman_order_number . ' expired because the linked premium product expired.', $receipt->id);
            }

            return true;
        });
    }

    private function newClaimCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (EarnedReward::where('claim_code', $code)->exists());

        return $code;
    }

    private function notify(int $userId, string $type, string $title, string $body, int $receiptId): void
    {
        CustomerNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => mb_substr($body, 0, 500),
            'receipt_id' => $receiptId,
            'created_at' => now(),
        ]);
    }
}
