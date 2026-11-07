<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_stock_form_cannot_restore_reserved_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = PremiumProduct::factory()->create(['stock' => 15]);
        $this->actingAs($admin)->put(route('products.update', $product), [
            'name' => $product->name, 'category' => 'home_care', 'is_perishable' => 0,
            'stock' => 20, 'expected_stock' => 20,
        ])->assertSessionHasErrors('stock');
        $this->assertSame(15, $product->fresh()->stock);
        $this->assertDatabaseCount('premium_stock_movements', 0);
    }

    public function test_normalized_catalog_names_are_unique_and_rename_preserves_promotion_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Inventory::factory()->create(['name' => 'Shampoo 1.5L']);
        $promo = Promotion::factory()->create(['buy_product_name' => $item->name]);
        $this->actingAs($admin)->post(route('admin.inventory.store'), [
            'name' => '  SHAMPOO   1.5L ', 'category' => 'home_care', 'stock_balance' => 0,
        ])->assertSessionHasErrors('name');
        $this->put(route('admin.inventory.update', $item), [
            'name' => 'Renamed Shampoo', 'category' => 'home_care', 'stock_balance' => 50, 'expected_stock' => 50,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Renamed Shampoo', $promo->fresh()->buy_product_name);
        $this->assertSame($item->id, $promo->fresh()->inventory_id);
        $this->delete(route('admin.inventory.destroy', $item))->assertSessionHasErrors('error');
    }

    public function test_dotted_product_reward_choice_and_customer_filter_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Inventory::factory()->create(['name' => 'Shampoo 1.5L']);
        $promo = Promotion::factory()->create(['buy_product_name' => $item->name]);
        Promotion::factory()->create(['buy_product_name' => $item->name]);
        $receipt = Receipt::factory()->create();
        $receipt->items()->create(['product_name' => $item->name, 'quantity' => 2, 'unit_price' => 0]);
        $this->actingAs($admin)->post(route('admin.receipts.approve', $receipt), ['selections' => [$item->name => $promo->id]])
            ->assertSessionHasNoErrors();
        $this->assertSame('approved', $receipt->fresh()->status);
        $this->get(route('admin.receipts', ['claim_filter' => 'awaiting_customer']))->assertOk();
    }
}
