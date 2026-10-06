<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\EarnedReward;
use App\Models\PremiumProduct;
use App\Services\ClaimService;
use App\Services\PremiumStockLedger;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lrts:expire-rewards', function (ClaimService $claims, PremiumStockLedger $ledger) {
    $expiredClaims = 0;
    EarnedReward::query()->whereIn('claim_status', ['unclaimed', 'claim_requested'])
        ->whereNotNull('expires_at')->where('expires_at', '<=', now())
        ->orderBy('id')->chunkById(100, function ($rewards) use ($claims, &$expiredClaims) {
            foreach ($rewards as $reward) {
                if ($claims->expire($reward)) $expiredClaims++;
            }
        });
    EarnedReward::query()->whereIn('claim_status', ['unclaimed', 'claim_requested'])
        ->whereHas('premiumProduct', fn ($query) => $query->where('is_perishable', true)->whereDate('expiry_date', '<', today()))
        ->orderBy('id')->chunkById(100, function ($rewards) use ($claims, &$expiredClaims) {
            foreach ($rewards as $reward) {
                if ($claims->expireForProduct($reward)) $expiredClaims++;
            }
        });

    $expiredStock = 0;
    PremiumProduct::query()->where('is_perishable', true)->whereDate('expiry_date', '<', today())
        ->where('stock', '>', 0)->orderBy('id')->chunkById(100, function ($products) use ($ledger, &$expiredStock) {
            foreach ($products as $product) {
                $quantity = (int) $product->stock;
                $ledger->move($product, -$quantity, 'expired_writeoff', $product, 'On-hand stock written off after product expiry.');
                $expiredStock += $quantity;
            }
        });

    $this->info("Expired {$expiredClaims} reward claim(s) and wrote off {$expiredStock} expired premium item(s).");
})->purpose('Expire reward claim windows and write off expired premium stock');

Schedule::command('lrts:expire-rewards')->dailyAt('00:10');
