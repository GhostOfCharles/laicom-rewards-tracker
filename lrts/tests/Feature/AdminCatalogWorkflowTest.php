<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCatalogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_dates_category_and_database_category_filter_work(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.inventory.store'), [
            'name' => 'Daily Shampoo',
            'category' => 'personal_care',
            'stock_balance' => 12,
            'entry_date' => today()->subDays(2)->toDateString(),
            'expiry_date' => today()->addDays(10)->toDateString(),
            'image' => UploadedFile::fake()->createWithContent('shampoo.png', file_get_contents(public_path('images/laicom-logo.png'))),
        ])->assertRedirect(route('admin.inventory'));

        $inventory = Inventory::where('name', 'Daily Shampoo')->firstOrFail();
        $this->assertSame('personal_care', $inventory->category);
        $this->assertSame(today()->subDays(2)->toDateString(), $inventory->entry_date->toDateString());
        $this->assertSame(today()->addDays(10)->toDateString(), $inventory->expiry_date->toDateString());
        Storage::disk('public')->assertExists($inventory->image_path);

        $this->actingAs($admin)->get(route('admin.inventory', ['category' => 'food']))
            ->assertOk()->assertDontSee('Daily Shampoo');
        $this->get(route('admin.inventory', ['category' => 'personal_care']))
            ->assertOk()->assertSee('Daily Shampoo')->assertSee('EXPIRING SOON');

        $this->put(route('admin.inventory.update', $inventory->id), [
            'name' => 'Daily Shampoo',
            'category' => 'food',
            'stock_balance' => 8,
            'entry_date' => today()->subDays(2)->toDateString(),
            'expiry_date' => today()->addDays(40)->toDateString(),
        ])->assertRedirect(route('admin.inventory'));

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'category' => 'food',
            'stock_balance' => 8,
            'expiry_date' => today()->addDays(40)->toDateString() . ' 00:00:00',
        ]);

        $this->from(route('admin.inventory'))->post(route('admin.inventory.store'), [
            'name' => 'Invalid Category Product',
            'category' => 'other',
            'stock_balance' => 1,
        ])->assertSessionHasErrors('category');
    }

    public function test_premium_product_category_crud_and_database_filter_work(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Reward Mug',
            'item_code' => 'PRM-MUG-1',
            'category' => 'home_care',
            'initial_stock' => 20,
            'is_perishable' => 0,
            'image' => UploadedFile::fake()->createWithContent('mug.png', file_get_contents(public_path('images/laicom-logo.png'))),
        ])->assertRedirect(route('admin.dashboard'));

        $product = PremiumProduct::where('item_code', 'PRM-MUG-1')->firstOrFail();
        Storage::disk('public')->assertExists($product->image_path);
        $this->get(route('admin.dashboard', ['premium_category' => 'food']))
            ->assertOk()->assertSee('NO PREMIUM PRODUCTS FOUND');
        $this->get(route('admin.dashboard', ['premium_category' => 'home_care']))
            ->assertOk()->assertSee('Reward Mug')->assertSee('Home Care');

        $this->put(route('products.update', $product->id), [
            'name' => 'Reward Mug',
            'category' => 'food',
            'stock' => 18,
            'is_perishable' => 0,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('premium_products', [
            'id' => $product->id,
            'category' => 'food',
            'stock' => 18,
        ]);

        $this->delete(route('products.destroy', $product->id))->assertRedirect();
        $this->assertDatabaseMissing('premium_products', ['id' => $product->id]);
    }

    public function test_promotion_crud_requires_real_qualifying_and_reward_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Inventory::create(['name' => 'Dish Soap', 'category' => 'home_care', 'stock_balance' => 10]);
        $reward = PremiumProduct::create([
            'item_code' => 'PRM-BOTTLE-1',
            'name' => 'Water Bottle',
            'category' => 'home_care',
            'stock' => 8,
        ]);

        $this->actingAs($admin)->post(route('promotions.store'), [
            'title' => 'Soap bottle reward',
            'buy_product_name' => 'Dish Soap',
            'required_quantity' => 3,
            'premium_product_id' => $reward->id,
            'reward_quantity' => 2,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonth()->toDateString(),
            'is_active' => 1,
        ])->assertRedirect();

        $promotion = Promotion::where('title', 'Soap bottle reward')->firstOrFail();
        $this->assertSame('Dish Soap', $promotion->buy_product_name);
        $this->assertSame($reward->id, $promotion->premium_product_id);

        $this->put(route('promotions.update', $promotion->id), [
            'title' => 'Updated soap reward',
            'buy_product_name' => 'Dish Soap',
            'required_quantity' => 4,
            'premium_product_id' => $reward->id,
            'reward_quantity' => 1,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonth()->toDateString(),
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'title' => 'Updated soap reward',
            'required_quantity' => 4,
            'reward_quantity' => 1,
        ]);

        $this->from(route('admin.dashboard'))->post(route('promotions.store'), [
            'buy_product_name' => 'Not in inventory',
            'required_quantity' => 1,
            'premium_product_id' => $reward->id,
            'reward_quantity' => 1,
        ])->assertSessionHasErrors('buy_product_name');

        $this->delete(route('promotions.destroy', $promotion->id))->assertRedirect();
        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }
}
