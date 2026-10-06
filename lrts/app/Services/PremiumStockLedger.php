<?php

namespace App\Services;

use App\Models\PremiumProduct;
use App\Models\PremiumStockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Services\ActivityLogger;

class PremiumStockLedger
{
    public function __construct(private readonly ActivityLogger $activityLogger)
    {
    }

    public function move(PremiumProduct $product, int $delta, string $type, ?Model $reference = null, ?string $note = null): PremiumStockMovement
    {
        return DB::transaction(function () use ($product, $delta, $type, $reference, $note) {
            $locked = PremiumProduct::query()->lockForUpdate()->findOrFail($product->id);
            $newBalance = (int) $locked->stock + $delta;
            if ($newBalance < 0) {
                throw new RuntimeException('Premium stock cannot become negative for ' . $locked->name . '.');
            }
            if ($delta > 0 && $locked->isExpired()) {
                throw new RuntimeException('Expired premium stock cannot be restocked for ' . $locked->name . '.');
            }

            $locked->update(['stock' => $newBalance]);
            $product->stock = $newBalance;

            $movement = PremiumStockMovement::create([
                'premium_product_id' => $locked->id,
                'type' => $type,
                'quantity' => $delta,
                'balance_after' => $newBalance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'user_id' => Auth::id(),
                'notes' => $note ? mb_substr($note, 0, 500) : null,
                'created_at' => now(),
            ]);
            $this->activityLogger->record('premium.stock_movement', 'Premium stock movement for ' . $locked->name . ': ' . ($delta > 0 ? '+' : '') . $delta . ' (' . $type . ').', $reference ?? $locked, [
                'premium_product_id' => $locked->id,
                'type' => $type,
                'quantity' => $delta,
                'balance_after' => $newBalance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);

            return $movement;
        });
    }
}
