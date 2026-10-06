<?php

namespace Tests\Feature;

use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptRewardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_submission_requires_a_slip_and_recent_non_future_order_date(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        Inventory::create(['name' => 'Laundry Soap', 'category' => 'home_care', 'stock_balance' => 50]);

        $this->actingAs($customer)->from(route('customer.dashboard'))->post(route('customer.submit_order'), [
            'salesman_order_number' => 'ORD-VALIDATION',
            'order_date' => today()->addDay()->toDateString(),
            'items' => [['product_name' => 'Laundry Soap', 'quantity' => 1]],
        ])->assertSessionHasErrors(['slip', 'order_date']);

        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_order_number_is_normalized_duplicate_checked_and_reusable_after_cancellation(): void
    {
        Storage::fake('private');
        $customer = User::factory()->create(['role' => 'customer']);
        Inventory::create(['name' => 'Laundry Soap', 'category' => 'home_care', 'stock_balance' => 50]);
        $payload = [
            'order_date' => today()->toDateString(),
            'slip' => $this->fakePng('receipt.png'),
            'items' => [['product_name' => 'Laundry Soap', 'quantity' => 1]],
        ];

        $this->actingAs($customer)->post(route('customer.submit_order'), $payload + ['salesman_order_number' => ' ord- 001 '])->assertRedirect();
        $receipt = Receipt::firstOrFail();
        $this->assertSame('ORD-001', $receipt->salesman_order_number);
        $this->actingAs($customer)->from(route('customer.dashboard'))->post(route('customer.submit_order'), $payload + ['salesman_order_number' => 'ORD-001'])
            ->assertSessionHasErrors('salesman_order_number');

        $this->post(route('customer.receipts.cancel', $receipt))->assertRedirect();
        $this->post(route('customer.submit_order'), $payload + ['salesman_order_number' => 'ORD-001'])->assertRedirect();
        $this->assertDatabaseCount('receipts', 2);
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('receipts', ['salesman_order_number' => 'ORD-001', 'status' => 'pending']);
    }

    public function test_claim_request_generates_notification_and_void_returns_reserved_stock_with_audit(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = PremiumProduct::create(['item_code' => 'PRM-VOID-1', 'name' => 'Reward Towel', 'category' => 'home_care', 'stock' => 3]);
        $promotion = Promotion::create([
            'title' => 'Towel reward', 'buy_product_name' => 'Soap', 'premium_product_id' => $product->id,
            'required_quantity' => 1, 'reward_quantity' => 2, 'start_date' => today()->subDay(),
            'end_date' => today()->addMonth(), 'is_active' => true,
        ]);
        $receipt = Receipt::create(['user_id' => $customer->id, 'salesman_order_number' => 'VOID-001', 'status' => 'approved']);
        $reward = EarnedReward::create([
            'user_id' => $customer->id, 'receipt_id' => $receipt->id, 'promotion_id' => $promotion->id,
            'premium_product_id' => $product->id, 'reward_quantity' => 2, 'claim_status' => 'unclaimed',
        ]);

        $this->actingAs($customer)->post(route('customer.receipts.claim', $receipt))->assertRedirect()->assertSessionHas('success');
        $reward->refresh();
        $this->assertSame('claim_requested', $reward->claim_status);
        $this->assertNotEmpty($reward->claim_code);
        $this->assertDatabaseHas('customer_notifications', ['user_id' => $customer->id, 'type' => 'reward.claim_requested']);
        $this->post(route('admin.rewards.release', $reward))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.rewards.void', $reward), ['reason' => 'Customer no longer wants this reward.'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('voided', $reward->fresh()->claim_status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseHas('premium_stock_movements', ['premium_product_id' => $product->id, 'type' => 'reward_returned', 'quantity' => 2, 'balance_after' => 5]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'reward.voided', 'subject_id' => $reward->id]);
    }

    public function test_customer_receipt_admin_approval_and_reward_claim_complete_once(): void
    {
        Storage::fake('private');
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
            'order_date' => today()->toDateString(),
            'slip' => $this->fakePng('receipt.png'),
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

        $this->actingAs($customer)->post(route('customer.receipts.claim', $receipt))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('claim_requested', $earnedReward->fresh()->claim_status);
        $this->actingAs($admin)->post(route('admin.rewards.release', $earnedReward))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('claimed', $earnedReward->fresh()->claim_status);
        $this->assertNotNull($earnedReward->fresh()->claimed_at);

        $adminPage = $this->actingAs($admin)->get(route('admin.receipts'))->assertOk();
        $adminPage->assertSee('PURCHASED PRODUCTS')->assertSee('REWARD DETAILS &amp; HISTORY', false);

        $rejectedReceipt = Receipt::create([
            'user_id' => $customer->id,
            'salesman_order_number' => 'ORD-WORKFLOW-REJECT',
            'status' => 'pending',
        ]);
        $this->post(route('admin.receipts.reject', $rejectedReceipt->id), ['reason' => 'Order number is not verified.'])
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

        $this->actingAs($customer)->post(route('customer.receipts.claim', $receipt))
            ->assertRedirect()
            ->assertSessionHasErrors(['error' => 'This reward is no longer available because the linked premium product has expired. Please contact support.']);

        $this->assertDatabaseHas('earned_rewards', ['id' => $reward->id, 'claim_status' => 'unclaimed', 'claimed_at' => null]);
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'approved']);
    }

    public function test_admin_cannot_approve_expired_reward_product_and_sees_warning(): void
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
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('earned_rewards', ['receipt_id' => $receipt->id]);
        $this->actingAs($admin)->get(route('admin.receipts'))->assertSee('WARNING: EXPIRED REWARD PRODUCT');
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j5WQAAAAASUVORK5CYII='));
    }
}
