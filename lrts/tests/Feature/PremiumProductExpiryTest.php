<?php

namespace Tests\Feature;

use App\Models\PremiumProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumProductExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_perishable_product_with_valid_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Fresh Treat Box',
            'item_code' => 'PRM-FRESH-1',
            'category' => 'food',
            'initial_stock' => 12,
            'is_perishable' => 1,
            'entry_date' => today()->toDateString(),
            'expiry_date' => today()->addDays(45)->toDateString(),
        ])->assertRedirect(route('admin.dashboard'));

        $product = PremiumProduct::where('item_code', 'PRM-FRESH-1')->firstOrFail();
        $this->assertTrue($product->is_perishable);
        $this->assertSame('healthy', $product->expiryStatus());
        $this->assertSame(45, $product->daysUntilExpiry());
    }

    public function test_admin_must_supply_valid_perishable_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $base = [
            'name' => 'Fresh Treat Box',
            'item_code' => 'PRM-FRESH-2',
            'category' => 'food',
            'initial_stock' => 12,
            'is_perishable' => 1,
        ];

        $this->actingAs($admin)->from(route('admin.dashboard'))->post(route('products.store'), $base)
            ->assertSessionHasErrors(['entry_date', 'expiry_date']);

        $this->post(route('products.store'), $base + [
            'entry_date' => today()->toDateString(),
            'expiry_date' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors('expiry_date');
    }

    public function test_non_perishable_dates_are_cleared_and_expiry_report_shows_statuses_and_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Reward Cup',
            'item_code' => 'PRM-CUP-EXPIRY',
            'category' => 'home_care',
            'initial_stock' => 5,
            'is_perishable' => 0,
            'entry_date' => today()->toDateString(),
            'expiry_date' => today()->addDays(3)->toDateString(),
        ])->assertRedirect(route('admin.dashboard'));

        $nonPerishable = PremiumProduct::where('item_code', 'PRM-CUP-EXPIRY')->firstOrFail();
        $this->assertFalse($nonPerishable->is_perishable);
        $this->assertNull($nonPerishable->entry_date);
        $this->assertNull($nonPerishable->expiry_date);

        PremiumProduct::create([
            'item_code' => 'PRM-EXPIRING-1', 'name' => 'Expiring Treat', 'category' => 'food', 'stock' => 2,
            'is_perishable' => true, 'entry_date' => today()->subDays(5), 'expiry_date' => today()->addDays(10),
        ]);
        PremiumProduct::create([
            'item_code' => 'PRM-EXPIRED-2', 'name' => 'Expired Treat', 'category' => 'food', 'stock' => 1,
            'is_perishable' => true, 'entry_date' => today()->subDays(20), 'expiry_date' => today()->subDays(4),
        ]);

        $dashboard = $this->get(route('admin.dashboard', ['premium_expiry' => 'expired']))->assertOk();
        preg_match('/<tbody>(.*?)<\/tbody>/s', $dashboard->getContent(), $productTableRows);
        $this->assertStringContainsString('Expired Treat', $productTableRows[1]);
        $this->assertStringNotContainsString('Expiring Treat', $productTableRows[1]);
        $this->assertStringNotContainsString('Reward Cup', $productTableRows[1]);

        $this->get(route('admin.reports', ['report_type' => 'premium_stock']))
            ->assertOk()
            ->assertSee('Expires in 10 days')
            ->assertSee('Expired 4 days ago')
            ->assertSee('PREMIUM PRODUCTS EXPIRING WITHIN 30 DAYS')
            ->assertSee('PREMIUM PRODUCTS ALREADY EXPIRED')
            ->assertSeeInOrder(['PREMIUM PRODUCTS EXPIRING WITHIN 30 DAYS', '>1</div>', 'PREMIUM PRODUCTS ALREADY EXPIRED', '>1</div>'], false);
    }
}
