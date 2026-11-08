<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\EarnedReward;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailableStockApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function order(int $stock = 3): array
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = PremiumProduct::create(['item_code' => 'AVAILABLE', 'name' => 'Reward towel', 'category' => 'home_care', 'stock' => $stock]);
        Promotion::create(['title' => 'Soap reward', 'buy_product_name' => 'Soap', 'premium_product_id' => $product->id, 'required_quantity' => 1, 'reward_quantity' => 1, 'start_date' => today()->subDay(), 'end_date' => today()->addMonth(), 'is_active' => true]);
        $receipt = Receipt::create(['user_id' => $customer->id, 'salesman_order_number' => 'AVAILABLE-1', 'status' => 'pending', 'order_date' => today()]);
        $receipt->items()->create(['product_name' => 'Soap', 'quantity' => 5, 'unit_price' => 0]);
        $this->actingAs($admin);
        return [$receipt, $product, $customer, $admin];
    }

    public function test_short_stock_requires_explicit_consent_and_reviewed_quantities(): void
    {
        [$receipt, $product] = $this->order();
        $this->post(route('admin.receipts.approve', $receipt))->assertSessionHasErrors('error');
        $this->post(route('admin.receipts.approve', $receipt), ['available_stock' => 1])->assertSessionHasErrors('award_quantities');
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame('pending', $receipt->fresh()->status);
        $this->assertDatabaseCount('earned_rewards', 0);
    }

    public function test_reduced_award_is_final_audited_and_follows_normal_claim_lifecycle(): void
    {
        [$receipt, $product, $customer, $admin] = $this->order();
        $payload = ['available_stock' => 1, 'award_quantities' => [3]];
        $this->post(route('admin.receipts.approve', $receipt), $payload)->assertSessionHasNoErrors()->assertSessionHas('success');
        $reward = EarnedReward::firstOrFail();
        $this->assertSame(3, $reward->reward_quantity);
        $this->assertSame(0, $product->fresh()->stock);
        $log = ActivityLog::where('action', 'receipt.approved')->firstOrFail()->properties;
        $this->assertTrue($log['reduced_award_confirmed']);
        $this->assertSame(5, $log['selections'][0]['eligible_quantity']);
        $this->assertSame(2, $log['selections'][0]['unawarded_quantity']);
        $this->assertDatabaseHas('premium_stock_movements', ['quantity' => -3, 'type' => 'reward_reserved']);
        $this->assertDatabaseHas('customer_notifications', ['type' => 'receipt.approved', 'body' => 'Your order AVAILABLE-1 was approved with 3 reward item(s) based on available stock. This is the final award; the remaining 2 item(s) will not be owed. View your rewards for details.']);
        $this->post(route('admin.receipts.approve', $receipt), $payload)->assertSessionHasErrors('error');
        $this->assertDatabaseCount('earned_rewards', 1);
        $this->actingAs($customer)->post(route('customer.receipts.claim', $receipt))->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.rewards.release', $reward))->assertSessionHas('success');
        $this->actingAs($customer)->post(route('customer.receipts.confirm-received', $receipt))->assertSessionHas('success');
        $this->assertSame('claimed', $reward->fresh()->claim_status);
        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_changed_stock_or_manipulated_quantity_requires_review(): void
    {
        [$receipt, $product] = $this->order();
        $product->update(['stock' => 2]);
        $this->post(route('admin.receipts.approve', $receipt), ['available_stock' => 1, 'award_quantities' => [3]])->assertSessionHasErrors('error');
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseCount('earned_rewards', 0);
    }

    public function test_zero_stock_and_expired_products_remain_blocked(): void
    {
        [$receipt, $product] = $this->order(0);
        $payload = ['available_stock' => 1, 'award_quantities' => [1]];
        $this->post(route('admin.receipts.approve', $receipt), $payload)->assertSessionHasErrors('error');
        $product->update(['stock' => 3, 'is_perishable' => true, 'entry_date' => today()->subMonth(), 'expiry_date' => today()->subDay()]);
        $this->post(route('admin.receipts.approve', $receipt), $payload)->assertSessionHasErrors('error');
        $this->assertDatabaseCount('earned_rewards', 0);
    }

    public function test_shared_stock_is_allocated_once_and_void_returns_only_awarded_stock(): void
    {
        [$receipt, $product] = $this->order(6);
        Promotion::create(['title' => 'Second reward', 'buy_product_name' => 'Shampoo', 'premium_product_id' => $product->id, 'required_quantity' => 1, 'reward_quantity' => 1, 'start_date' => today()->subDay(), 'end_date' => today()->addMonth(), 'is_active' => true]);
        $receipt->items()->create(['product_name' => 'Shampoo', 'quantity' => 5, 'unit_price' => 0]);
        $this->post(route('admin.receipts.approve', $receipt))->assertSessionHasErrors('error');
        $this->post(route('admin.receipts.approve', $receipt), ['available_stock' => 1, 'award_quantities' => [5, 1]])->assertSessionHasNoErrors();
        $this->assertSame(6, (int) EarnedReward::sum('reward_quantity'));
        $this->assertSame(0, $product->fresh()->stock);
        $reward = EarnedReward::where('buy_product_name', 'Shampoo')->firstOrFail();
        $this->post(route('admin.rewards.void', $reward), ['reason' => 'Customer declined reward.'])->assertSessionHasNoErrors();
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_full_stock_keeps_normal_approval_and_panel_shows_existing_styles(): void
    {
        [$receipt, $product] = $this->order(8);
        $this->get(route('admin.receipts.panel', $receipt))->assertOk()->assertSee('data-reward-approval', false)->assertSee('alert alert-warning', false)->assertSee('remaining quantity will not be owed');
        $this->post(route('admin.receipts.approve', $receipt))->assertSessionHasNoErrors();
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(5, EarnedReward::firstOrFail()->reward_quantity);
    }
}
