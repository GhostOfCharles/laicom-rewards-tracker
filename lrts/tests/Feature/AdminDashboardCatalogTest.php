<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_premium_search_category_stock_and_availability_filters_use_database_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createPremium('High Reward', 'PRM-HIGH-001', 80, 'home_care');
        $this->createPremium('Mid Reward', 'PRM-MID-001', 25, 'home_care');
        $this->createPremium('Low Reward', 'PRM-LOW-001', 5, 'food');
        $this->createPremium('Empty Reward', 'PRM-EMPTY-001', 0, 'food');

        $highResult = $this->actingAs($admin)->get(route('admin.dashboard', [
            'premium_search' => 'prm-high',
            'premium_category' => 'home_care',
            'premium_stock' => 'high',
            'premium_availability' => 'available',
        ]))->assertOk();
        $highRows = $this->firstTableBody($highResult->getContent());
        $this->assertStringContainsString('High Reward', $highRows);
        $this->assertStringNotContainsString('Mid Reward', $highRows);
        $this->assertStringNotContainsString('Low Reward', $highRows);

        $emptyResult = $this->get(route('admin.dashboard', [
            'premium_availability' => 'out',
        ]))->assertOk();
        $emptyRows = $this->firstTableBody($emptyResult->getContent());
        $this->assertStringContainsString('Empty Reward', $emptyRows);
        $this->assertStringNotContainsString('High Reward', $emptyRows);
        $this->assertStringContainsString('OUT OF STOCK', $emptyRows);

        $this->get(route('admin.dashboard', ['premium_search' => 'no-such-code']))
            ->assertOk()->assertSee('NO PREMIUM PRODUCTS FOUND');

        $smallCatalog = $this->get(route('admin.dashboard'))->assertOk();
        $smallCatalog->assertDontSee('id="allPremiumProductsModal"', false)
            ->assertDontSee('id="allPromotionsModal"', false);
    }

    public function test_tables_are_limited_to_five_and_show_more_modals_contain_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inventory = Inventory::create(['name' => 'Dish Soap', 'category' => 'home_care', 'stock_balance' => 20]);
        $product = null;

        for ($index = 1; $index <= 6; $index++) {
            $product = $this->createPremium('Reward ' . $index, 'PRM-LIMIT-' . $index, 20, 'home_care');
            Promotion::create([
                'title' => 'Promotion ' . $index,
                'buy_product_name' => $inventory->name,
                'premium_product_id' => $product->id,
                'required_quantity' => 2,
                'reward_quantity' => 1,
                'start_date' => today(),
                'end_date' => today()->addMonth(),
                'is_active' => $index < 6,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $html = $response->getContent();
        preg_match_all('/<tbody>(.*?)<\/tbody>/s', $html, $bodies);
        $this->assertCount(4, $bodies[1]);
        $this->assertSame(5, substr_count($bodies[1][0], '<tr'));
        $this->assertSame(5, substr_count($bodies[1][1], '<tr'));
        $this->assertSame(6, substr_count($bodies[1][2], '<tr'));
        $this->assertSame(6, substr_count($bodies[1][3], '<tr'));

        $response->assertSee('Showing 5 of 6 products')
            ->assertSee('Showing 5 of 6 promotions')
            ->assertSee('SHOW MORE')
            ->assertSee('id="allPremiumProductsModal"', false)
            ->assertSee('id="allPromotionsModal"', false)
            ->assertSee('modal-dialog-scrollable', false)
            ->assertSee('id="editPremiumProductModal' . $product->id . '"', false)
            ->assertSee('id="editPromotionModal' . Promotion::orderBy('id')->first()->id . '"', false)
            ->assertSee('data-bs-target="#editPremiumProductModal' . $product->id . '"', false)
            ->assertSee('table-responsive', false);

        preg_match_all('/data-bs-target="([^"]+)"/', $html, $targets);
        preg_match_all('/id="([^"]+)"/', $html, $ids);
        foreach ($targets[1] as $target) {
            $this->assertContains(ltrim($target, '#'), $ids[1]);
        }

        $inactivePage = $this->get(route('admin.dashboard', ['promotion_status' => 'inactive']))->assertOk();
        preg_match_all('/<tbody>(.*?)<\/tbody>/s', $inactivePage->getContent(), $filteredBodies);
        $this->assertStringContainsString('Promotion 6', $filteredBodies[1][1]);
        $this->assertStringNotContainsString('Promotion 5', $filteredBodies[1][1]);

        $searchPage = $this->get(route('admin.dashboard', ['promotion_search' => 'promotion 1']))->assertOk();
        preg_match_all('/<tbody>(.*?)<\/tbody>/s', $searchPage->getContent(), $searchBodies);
        $this->assertStringContainsString('Promotion 1', $searchBodies[1][1]);
        $this->assertStringNotContainsString('Promotion 2', $searchBodies[1][1]);
    }

    public function test_new_premium_product_names_cannot_duplicate_existing_names_by_case_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createPremium('Knorr', 'PRM-KNORR-001', 123, 'food');
        $legacyDuplicate = $this->createPremium('knorr', 'PRM-KNORR-002', 100, 'food');

        $this->actingAs($admin)->from(route('admin.dashboard'))->post(route('products.store'), [
            'name' => 'knorr',
            'item_code' => 'PRM-KNORR-DUP',
            'category' => 'food',
            'initial_stock' => 100,
            'is_perishable' => 0,
        ])->assertSessionHasErrors('name');

        $this->put(route('products.update', $legacyDuplicate->id), [
            'name' => 'knorr',
            'stock' => 99,
            'category' => 'food',
            'is_perishable' => 0,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseCount('premium_products', 2);
        $this->assertDatabaseHas('premium_products', ['id' => $legacyDuplicate->id, 'name' => 'knorr', 'stock' => 99]);
    }

    private function createPremium(string $name, string $itemCode, int $stock, string $category): PremiumProduct
    {
        return PremiumProduct::create([
            'name' => $name,
            'item_code' => $itemCode,
            'stock' => $stock,
            'category' => $category,
            'is_perishable' => false,
        ]);
    }

    private function firstTableBody(string $html): string
    {
        preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $matches);
        return $matches[1] ?? '';
    }
}
