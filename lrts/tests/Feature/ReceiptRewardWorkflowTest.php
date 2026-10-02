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
}
