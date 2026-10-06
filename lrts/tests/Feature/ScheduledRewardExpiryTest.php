<?php

namespace Tests\Feature;

use App\Models\EarnedReward;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ScheduledRewardExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_command_returns_unclaimed_stock_and_writes_off_expired_products(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $returnableProduct = PremiumProduct::create(['item_code' => 'EXP-RETURN', 'name' => 'Returnable Reward', 'category' => 'home_care', 'stock' => 3]);
        $expiredProduct = PremiumProduct::create([
            'item_code' => 'EXP-WRITEOFF', 'name' => 'Expired Reward', 'category' => 'food', 'stock' => 4,
            'is_perishable' => true, 'entry_date' => today()->subMonth(), 'expiry_date' => today()->subDay(),
        ]);
        $returnablePromotion = $this->promotion($returnableProduct, 'Returnable promo');
        $expiredPromotion = $this->promotion($expiredProduct, 'Expired promo');
        $returnableReceipt = Receipt::create(['user_id' => $customer->id, 'salesman_order_number' => 'EXP-ORDER-1', 'status' => 'approved']);
        $expiredReceipt = Receipt::create(['user_id' => $customer->id, 'salesman_order_number' => 'EXP-ORDER-2', 'status' => 'approved']);
        $dueReward = EarnedReward::create([
            'user_id' => $customer->id, 'receipt_id' => $returnableReceipt->id, 'promotion_id' => $returnablePromotion->id,
            'premium_product_id' => $returnableProduct->id, 'reward_quantity' => 2, 'claim_status' => 'unclaimed', 'expires_at' => now()->subMinute(),
        ]);
        $productExpiredReward = EarnedReward::create([
            'user_id' => $customer->id, 'receipt_id' => $expiredReceipt->id, 'promotion_id' => $expiredPromotion->id,
            'premium_product_id' => $expiredProduct->id, 'reward_quantity' => 1, 'claim_status' => 'claim_requested', 'expires_at' => now()->addWeek(),
        ]);

        Artisan::call('lrts:expire-rewards');

        $this->assertSame('expired', $dueReward->fresh()->claim_status);
        $this->assertSame(5, $returnableProduct->fresh()->stock);
        $this->assertSame('expired', $productExpiredReward->fresh()->claim_status);
        $this->assertSame(0, $expiredProduct->fresh()->stock);
        $this->assertDatabaseHas('premium_stock_movements', ['premium_product_id' => $returnableProduct->id, 'type' => 'reward_returned', 'quantity' => 2]);
        $this->assertDatabaseHas('premium_stock_movements', ['premium_product_id' => $expiredProduct->id, 'type' => 'expired_writeoff', 'quantity' => -4]);
        $this->assertDatabaseHas('customer_notifications', ['user_id' => $customer->id, 'type' => 'reward.expired']);
    }

    private function promotion(PremiumProduct $product, string $title): Promotion
    {
        return Promotion::create([
            'title' => $title, 'buy_product_name' => 'Sample', 'premium_product_id' => $product->id,
            'required_quantity' => 1, 'reward_quantity' => 1, 'start_date' => today()->subMonth(),
            'end_date' => today()->addMonth(), 'is_active' => true,
        ]);
    }
}
