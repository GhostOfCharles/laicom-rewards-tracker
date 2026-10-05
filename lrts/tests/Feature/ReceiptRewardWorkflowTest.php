<?php

namespace Tests\Feature;

use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptRewardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_receipt_admin_approval_and_reward_claim_complete_once(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        Inventory::create(['name' => 'Laundry Soap', 'category' => 'home_care', 'stock_balance' => 50]);
        $rewardProduct = PremiumProduct::create([
            'item_code' => 'PRM-TOWEL-1',
            'name' => 'Reward Towel',
            'category' => 'home_care',
            'stock' => 5,
        ]);
        $promotion = Promotion::create([
            'title' => 'Buy two get one',
            'buy_product_name' => 'Laundry Soap',
            'premium_product_id' => $rewardProduct->id,
            'required_quantity' => 2,
            'reward_quantity' => 1,
            'start_date' => today(),
            'end_date' => today()->addYear(),
            'is_active' => true,
        ]);

        $this->actingAs($customer)->post(route('customer.submit_order'), [
            'salesman_order_number' => 'ORD-WORKFLOW-1',
            'items' => [
                ['product_name' => 'Laundry Soap', 'quantity' => 1],
                ['product_name' => 'Laundry Soap', 'quantity' => 1],
            ],
        ])->assertRedirect(route('customer.dashboard'));

        $receipt = Receipt::where('salesman_order_number', 'ORD-WORKFLOW-1')->firstOrFail();
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'pending']);

        $this->actingAs($admin)->post(route('admin.receipts.approve', $receipt->id))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(4, $rewardProduct->fresh()->stock);
        $earnedReward = EarnedReward::where('receipt_id', $receipt->id)->firstOrFail();
        $this->assertSame('unclaimed', $earnedReward->claim_status);
        $this->assertSame(1, $earnedReward->reward_quantity);

        $this->post(route('admin.receipts.approve', $receipt->id))
            ->assertSessionHasErrors('error');
        $this->assertSame(4, $rewardProduct->fresh()->stock);
        $this->assertSame(1, EarnedReward::where('receipt_id', $receipt->id)->count());

        $this->actingAs($customer)->post(route('customer.rewards.claim', $earnedReward))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('claimed', $earnedReward->fresh()->claim_status);
        $this->assertNotNull($earnedReward->fresh()->claimed_at);

        $adminPage = $this->actingAs($admin)->get(route('admin.receipts'))->assertOk();
        $html = $adminPage->getContent();
        $this->assertGreaterThan(strrpos($html, '</table>'), strpos($html, 'id="viewProductsModal' . $receipt->id . '"'));

        $rejectedReceipt = Receipt::create([
            'user_id' => $customer->id,
            'salesman_order_number' => 'ORD-WORKFLOW-REJECT',
            'status' => 'pending',
        ]);
        $this->post(route('admin.receipts.reject', $rejectedReceipt->id))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('receipts', ['id' => $rejectedReceipt->id, 'status' => 'rejected']);
    }

    public function test_customer_cannot_claim_reward_for_expired_perishable_product(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $premiumProduct = PremiumProduct::create([
            'item_code' => 'PRM-EXPIRED-1',
            'name' => 'Expired Snack Box',
            'category' => 'food',
            'stock' => 2,
            'is_perishable' => true,
            'entry_date' => today()->subMonth(),
            'expiry_date' => today()->subDay(),
        ]);
        $receipt = Receipt::create([
            'user_id' => $customer->id,
            'salesman_order_number' => 'ORD-EXPIRED-REWARD',
            'status' => 'approved',
        ]);
        $promotion = Promotion::create([
            'title' => 'Expired reward promo',
            'buy_product_name' => 'Snack',
            'premium_product_id' => $premiumProduct->id,
            'required_quantity' => 1,
            'reward_quantity' => 1,
            'start_date' => today()->subMonth(),
            'end_date' => today()->addMonth(),
            'is_active' => true,
        ]);
        $reward = EarnedReward::create([
            'user_id' => $customer->id,
            'receipt_id' => $receipt->id,
            'promotion_id' => $promotion->id,
            'premium_product_id' => $premiumProduct->id,
            'reward_quantity' => 1,
            'claim_status' => 'unclaimed',
        ]);

        $this->actingAs($customer)->post(route('customer.rewards.claim', $reward))
            ->assertRedirect()
            ->assertSessionHasErrors(['error' => 'This reward is no longer available because the linked premium product has expired. Please contact support.']);

        $this->assertDatabaseHas('earned_rewards', ['id' => $reward->id, 'claim_status' => 'unclaimed', 'claimed_at' => null]);
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'approved']);
    }

    public function test_admin_can_approve_receipt_with_expired_reward_and_sees_warning(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        Inventory::create(['name' => 'Snack', 'category' => 'food', 'stock_balance' => 10]);
        $product = PremiumProduct::create([
            'item_code' => 'PRM-EXPIRED-APPROVAL',
            'name' => 'Expired Snack',
            'category' => 'food',
            'stock' => 3,
            'is_perishable' => true,
            'entry_date' => today()->subMonth(),
            'expiry_date' => today()->subDay(),
        ]);
        Promotion::create([
            'title' => 'Snack reward',
            'buy_product_name' => 'Snack',
            'premium_product_id' => $product->id,
            'required_quantity' => 1,
            'reward_quantity' => 1,
            'start_date' => today()->subMonth(),
            'end_date' => today()->addMonth(),
            'is_active' => true,
        ]);
        $receipt = Receipt::create([
            'user_id' => $customer->id,
            'salesman_order_number' => 'ORD-EXPIRED-APPROVAL',
            'status' => 'pending',
        ]);
        $receipt->items()->create(['product_name' => 'Snack', 'quantity' => 1, 'unit_price' => 0]);

        $this->actingAs($admin)->post(route('admin.receipts.approve', $receipt->id))
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Warning: one or more linked perishable premium products have expired.'));

        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'approved']);
        $this->assertDatabaseHas('earned_rewards', ['receipt_id' => $receipt->id, 'premium_product_id' => $product->id]);
        $this->actingAs($admin)->get(route('admin.receipts'))->assertSee('WARNING: EXPIRED REWARD PRODUCT');
    }
}
