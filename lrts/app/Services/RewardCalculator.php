<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Promotion;
use App\Models\Receipt;

class RewardCalculator
{
    private array $promotionCache = [];

    public function forReceipt(Receipt $receipt, bool $includeInactivePromotions = false): array
    {
        $receipt->loadMissing('items');
        $orderDate = $receipt->order_date?->toDateString()
            ?? $receipt->submitted_at?->toDateString()
            ?? today()->toDateString();

        $cacheKey = $orderDate.':'.(int) $includeInactivePromotions;
        $promotions = $this->promotionCache[$cacheKey] ??= Promotion::with('premiumProduct')
            ->when(! $includeInactivePromotions, fn ($query) => $query->where('is_active', true))
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $orderDate))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $orderDate))
            ->get();

        $groups = [];
        foreach ($receipt->items->groupBy(fn ($item) => $item->inventory_id ? 'id:'.$item->inventory_id : Inventory::nameKey($item->product_name)) as $key => $items) {
            $purchasedQuantity = (int) $items->sum('quantity');
            $options = [];

            foreach ($promotions->filter(fn ($promotion) => $promotion->inventory_id && $items->first()->inventory_id ? $promotion->inventory_id === $items->first()->inventory_id : Inventory::nameKey($promotion->buy_product_name) === Inventory::nameKey($items->first()->product_name)) as $promotion) {
                $required = max(1, (int) $promotion->required_quantity);
                $multiplier = intdiv($purchasedQuantity, $required);
                if ($multiplier < 1) {
                    continue;
                }

                $product = $promotion->premiumProduct;
                if (! $product) {
                    continue;
                }

                $rewardQuantity = $multiplier * (int) $promotion->reward_quantity;
                $options[] = [
                    'promotion_id' => $promotion->id,
                    'promotion_title' => $promotion->title,
                    'buy_product_name' => $promotion->buy_product_name,
                    'required_quantity' => $required,
                    'promo_reward_quantity' => (int) $promotion->reward_quantity,
                    'premium_product_id' => $product->id,
                    'premium_name' => $product->name,
                    'premium_image' => $product->image_path,
                    'product_expired' => $product->isExpired(),
                    'multiplier' => $multiplier,
                    'reward_quantity' => $rewardQuantity,
                    'stock_available' => (int) $product->stock,
                    'in_stock' => ! $product->isExpired() && (int) $product->stock >= $rewardQuantity,
                ];
            }

            if ($options !== []) {
                $groups[] = [
                    'buy_product_name' => $items->first()->product_name,
                    'purchased_quantity' => $purchasedQuantity,
                    'needs_choice' => count($options) > 1,
                    'options' => $options,
                ];
            }
        }

        return $groups;
    }
}
