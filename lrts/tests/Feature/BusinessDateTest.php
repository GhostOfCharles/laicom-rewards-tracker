<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_dates_and_promotion_visibility_use_manila_calendar_without_changing_utc_storage(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 17:00:00', 'UTC'));
        Storage::fake('private');
        $item = Inventory::factory()->create();
        $promotion = Promotion::factory()->create(['title' => 'Midnight promotion', 'buy_product_name' => $item->name, 'start_date' => '2026-11-09', 'end_date' => '2026-11-09']);
        $this->actingAs(User::factory()->create())->get(route('customer.dashboard'))->assertOk()->assertSee('Midnight promotion')->assertSee('max="2026-11-09"', false);
        $payload = ['salesman_order_number' => 'MIDNIGHT-ORDER', 'order_date' => '2026-11-09', 'slip' => $this->fakePng('receipt.png'), 'items' => [['product_name' => $item->name, 'quantity' => 2]]];
        $this->post(route('customer.submit_order'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('receipts', ['salesman_order_number' => 'MIDNIGHT-ORDER', 'submitted_at' => '2026-11-08 17:00:00']);
        $this->postJson(route('customer.submit_order'), [...$payload, 'salesman_order_number' => 'FUTURE', 'order_date' => '2026-11-10'])->assertUnprocessable();
        $this->postJson(route('customer.submit_order'), [...$payload, 'salesman_order_number' => 'TOO-OLD', 'order_date' => '2026-09-09'])->assertUnprocessable();
        $this->assertSame('UTC', config('app.timezone'));
    }

    public function test_product_expiry_switches_at_manila_midnight_and_scheduler_uses_business_timezone(): void
    {
        $product = PremiumProduct::factory()->create(['is_perishable' => true, 'expiry_date' => '2026-11-08']);
        $this->travelTo(Carbon::parse('2026-11-08 15:59:00', 'UTC'));
        $this->assertFalse($product->isExpired());
        $this->assertSame(0, $product->daysUntilExpiry());
        $this->travelTo(Carbon::parse('2026-11-08 16:00:00', 'UTC'));
        $this->assertTrue($product->isExpired());
        $this->artisan('lrts:expire-rewards')->assertSuccessful();
        $this->assertSame(0, $product->fresh()->stock);
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'lrts:expire-rewards'));
        $this->assertSame('Asia/Manila', $event->timezone);
    }

    public function test_report_day_boundaries_use_manila_dates_for_utc_records(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 17:00:00', 'UTC'));
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Inventory::factory()->create(['name' => 'Report boundary product']);
        $item->stockMovements()->make(['type' => 'inflow', 'quantity' => 7])->forceFill(['created_at' => '2026-11-08 16:30:00'])->save();
        $item->stockMovements()->make(['type' => 'inflow', 'quantity' => 100])->forceFill(['created_at' => '2026-11-08 15:59:00'])->save();
        $response = $this->actingAs($admin)->get(route('admin.reports', ['report_type' => 'inventory_movement', 'range' => 'today']))->assertOk();
        $this->assertSame('2026-11-09', $response->viewData('fromDate'));
        $this->assertSame(7, $response->viewData('rows')[0][3]);
    }

    public function test_inventory_form_validation_and_expiry_badges_share_the_manila_calendar(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 17:00:00', 'UTC'));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $payload = ['name' => 'Midnight inventory', 'category' => 'home_care', 'stock_balance' => 10, 'entry_date' => '2026-11-09', 'expiry_date' => '2026-12-09'];
        $this->post(route('admin.inventory.store'), $payload)->assertSessionHasNoErrors();
        $item = Inventory::where('name', $payload['name'])->firstOrFail();
        $this->put(route('admin.inventory.update', $item), [...$payload, 'expected_stock' => 10])->assertSessionHasNoErrors();
        $this->postJson(route('admin.inventory.store'), [...$payload, 'name' => 'Future entry', 'entry_date' => '2026-11-10'])->assertUnprocessable();
        $page = $this->get(route('admin.inventory'))->assertOk();
        $document = new \DOMDocument;
        $document->loadHTML($page->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $row = (new \DOMXPath($document))->query('//tbody/tr[td[contains(., "Midnight inventory")]]')->item(0);
        $this->assertNotNull($row);
        $this->assertStringContainsString('EXPIRING SOON', $row->textContent);
        $this->get(route('admin.reports', ['report_type' => 'inventory_movement']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows[0][6] === 'EXPIRING SOON');
    }
}
