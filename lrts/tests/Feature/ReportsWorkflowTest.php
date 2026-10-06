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

class ReportsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_show_live_promotion_premium_and_inventory_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $premium = PremiumProduct::create([
            'item_code' => 'RPT-REWARD-1',
            'name' => 'Reward Cup',
            'category' => 'home_care',
            'stock' => 8,
        ]);
        $promotion = Promotion::create([
            'title' => 'Cup for soap',
            'buy_product_name' => 'Dish Soap',
            'premium_product_id' => $premium->id,
            'required_quantity' => 2,
            'reward_quantity' => 1,
            'start_date' => today()->subYear(),
            'end_date' => today()->addYear(),
            'is_active' => true,
        ]);

        $approved = Receipt::create([
            'user_id' => User::factory()->create(['role' => 'customer'])->id,
            'salesman_order_number' => 'RPT-APPROVED',
            'status' => 'approved',
            'submitted_at' => now()->subDays(4),
        ]);
        $approved->items()->create(['product_name' => 'Dish Soap', 'quantity' => 2, 'unit_price' => 0]);
        EarnedReward::create([
            'user_id' => $approved->user_id,
            'receipt_id' => $approved->id,
            'promotion_id' => $promotion->id,
            'premium_product_id' => $premium->id,
            'reward_quantity' => 1,
            'claim_status' => 'claimed',
            'claimed_at' => now()->subDays(2),
            'created_at' => now()->subDays(4),
        ]);

        $rejected = Receipt::create([
            'user_id' => $approved->user_id,
            'salesman_order_number' => 'RPT-REJECTED',
            'status' => 'rejected',
            'submitted_at' => now()->subDays(3),
        ]);
        $rejected->items()->create(['product_name' => 'Dish Soap', 'quantity' => 3, 'unit_price' => 0]);

        $this->actingAs($admin)->get(route('admin.reports', ['report_type' => 'promo_performance', 'range' => 'last_30_days']))
            ->assertOk()->assertSee('Cup for soap')->assertSee('TOTAL RECEIPTS SUBMITTED')->assertSee('Range:');
        $this->get(route('admin.reports', ['report_type' => 'premium_stock', 'range' => 'last_30_days']))
            ->assertOk()->assertSee('Reward Cup')->assertSee('LOW')
            ->assertSee('PRODUCTS BELOW LOW-STOCK THRESHOLD', false);

        $this->post(route('admin.inventory.store'), [
            'name' => 'Expiring Soap',
            'category' => 'home_care',
            'stock_balance' => 20,
            'entry_date' => today()->subDays(1)->toDateString(),
            'expiry_date' => today()->addDays(10)->toDateString(),
        ])->assertRedirect();
        $item = Inventory::where('name', 'Expiring Soap')->firstOrFail();
        $this->put(route('admin.inventory.update', $item->id), [
            'name' => 'Expiring Soap',
            'category' => 'home_care',
            'stock_balance' => 17,
            'entry_date' => today()->subDays(1)->toDateString(),
            'expiry_date' => today()->addDays(10)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_movements', [
            'inventory_id' => $item->id,
            'type' => 'inflow',
            'quantity' => 20,
            'reference' => 'Admin add',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'inventory_id' => $item->id,
            'type' => 'outflow',
            'quantity' => 3,
            'reference' => 'Admin edit',
        ]);

        $this->get(route('admin.reports', ['report_type' => 'inventory_movement', 'range' => 'last_30_days']))
            ->assertOk()->assertSee('Expiring Soap')->assertSee('EXPIRING SOON')
            ->assertSee('Current Stock')->assertSee('TOTAL INFLOW QUANTITY', false);
    }

    public function test_report_csv_and_xlsx_exports_download_and_custom_ranges_validate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Promotion::create([
            'title' => 'Export Promo',
            'buy_product_name' => 'Example Product',
            'premium_product_id' => PremiumProduct::create([
                'item_code' => 'RPT-EXPORT-1', 'name' => 'Export Reward', 'category' => 'food', 'stock' => 5,
            ])->id,
            'required_quantity' => 2,
            'reward_quantity' => 1,
            'start_date' => today(),
            'end_date' => today()->addMonth(),
            'is_active' => true,
        ]);

        $csv = $this->actingAs($admin)->get(route('admin.reports', [
            'report_type' => 'promo_performance', 'range' => 'last_30_days', 'export_format' => 'csv',
        ]));
        $csv->assertDownload();
        $this->assertStringContainsString('promotion-performance-', $csv->headers->get('content-disposition'));
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('Promotion Performance', $csvContent);
        $this->assertStringContainsString('Range: ', $csvContent);
        $this->assertStringContainsString('Generated: ', $csvContent);
        $this->assertStringContainsString('Promotion Title', $csvContent);
        $this->assertStringContainsString('Export Promo', $csvContent);
        $this->assertStringContainsString('SUMMARY TOTALS', $csvContent);
        $this->assertStringContainsString('Total Receipts Submitted', $csvContent);

        $xlsx = $this->get(route('admin.reports', [
            'report_type' => 'premium_stock', 'range' => 'last_30_days', 'export_format' => 'xlsx',
        ]));
        $xlsx->assertDownload();
        $this->assertStringContainsString('premium-reward-stock-movement-', $xlsx->headers->get('content-disposition'));
        if (class_exists(\ZipArchive::class)) {
            $xlsxFile = $xlsx->baseResponse->getFile()->getPathname();
            $workbook = new \ZipArchive();
            $this->assertSame(true, $workbook->open($xlsxFile));
            $sheetXml = $workbook->getFromName('xl/worksheets/sheet1.xml');
            $workbook->close();
            $this->assertStringContainsString('A6', $sheetXml);
        }

        $this->from(route('admin.reports'))->get(route('admin.reports', [
            'report_type' => 'inventory_movement', 'range' => 'custom', 'start_date' => 'not-a-date', 'end_date' => today()->toDateString(),
        ]))->assertSessionHasErrors('start_date');

        $this->from(route('admin.reports'))->get(route('admin.reports', [
            'report_type' => 'inventory_movement', 'range' => 'custom', 'start_date' => today()->toDateString(), 'end_date' => today()->subDay()->toDateString(),
        ]))->assertSessionHasErrors('start_date');

        $this->get(route('admin.reports', [
            'report_type' => 'premium_stock',
            'range' => 'custom',
            'start_date' => today()->subDays(3)->toDateString(),
            'end_date' => today()->toDateString(),
        ]))->assertOk()->assertSee('Range: ' . today()->subDays(3)->format('M d, Y') . ' to ' . today()->format('M d, Y'));
    }
}
