<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_archive_and_every_reward_must_be_finished(): void
    {
        $receipt = Receipt::factory()->create(['status' => 'approved']);
        $this->post(route('admin.receipts.archive', $receipt))->assertRedirect(route('login.admin'));
        $this->actingAs($receipt->user)->post(route('admin.receipts.archive', $receipt))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $pending = Receipt::factory()->create(['status' => 'pending']);
        $this->postJson(route('admin.receipts.archive', $pending))->assertUnprocessable();

        $reward = EarnedReward::factory()->create(['receipt_id' => $receipt->id, 'user_id' => $receipt->user_id]);
        foreach (['unclaimed', 'claim_requested', 'released'] as $state) {
            $reward->update(['claim_status' => $state]);
            $this->postJson(route('admin.receipts.archive', $receipt))->assertUnprocessable();
            $this->assertNull($receipt->fresh()->archived_at);
        }
        $reward->update(['claim_status' => 'claimed']);
        $second = EarnedReward::factory()->create(['receipt_id' => $receipt->id, 'user_id' => $receipt->user_id, 'claim_status' => 'expired']);
        $this->post(route('admin.receipts.archive', $receipt))->assertSessionHasNoErrors();
        $this->assertNotNull($receipt->fresh()->archived_at);
        $this->assertSame('claimed', $reward->fresh()->claim_status);
        $this->assertSame('expired', $second->fresh()->claim_status);
        $this->post(route('admin.receipts.archive', $receipt))->assertSessionHasNoErrors();
        $this->assertSame(1, ActivityLog::where('action', 'receipt.archived')->count());
    }

    public function test_archived_receipts_are_searchable_read_only_and_retained_in_reports(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['name' => 'Archive Customer']);
        $receipt = Receipt::factory()->create(['user_id' => $customer->id, 'status' => 'approved', 'salesman_order_number' => 'HIST-123', 'submitted_at' => now()->subDays(4), 'slip_path' => 'receipts/history.png']);
        $receipt->items()->create(['product_name' => 'Historic soap', 'quantity' => 2, 'unit_price' => 0]);
        Storage::disk('private')->put('receipts/history.png', 'receipt file');
        $reward = EarnedReward::factory()->create(['receipt_id' => $receipt->id, 'user_id' => $customer->id, 'claim_status' => 'claimed', 'reward_quantity' => 2]);

        $this->actingAs($admin);
        $report = route('admin.reports', ['range' => 'last_30_days']);
        $before = $this->get($report)->assertOk()->viewData('summaries');
        $this->get(route('admin.receipts.panel', $receipt))->assertSee('Archive receipt');
        $this->post(route('admin.receipts.archive', $receipt))->assertRedirect(route('admin.receipts', ['archive' => 'archived']));
        Cache::flush();
        $this->assertSame($before, $this->get($report)->assertOk()->viewData('summaries'));
        $this->get(route('admin.receipts', ['status' => '']))->assertDontSee('HIST-123');
        foreach (['HIST-123', 'Archive Customer', (string) $receipt->id] as $search) {
            $this->get(route('admin.receipts', ['archive' => 'archived', 'search' => $search]))->assertOk()->assertSee('HIST-123')->assertSee('name="archive" value="archived"', false);
        }
        $this->get(route('admin.receipts', ['archive' => 'archived', 'date_from' => now()->subDays(5)->toDateString(), 'date_to' => now()->subDays(3)->toDateString()]))->assertSee('HIST-123');
        $this->get(route('admin.receipts', ['archive' => 'archived', 'date_from' => today()->toDateString()]))->assertDontSee('HIST-123');
        $this->get(route('admin.receipts', ['archive' => 'archived', 'date_to' => today()->toDateString()]))->assertOk()->assertSee('HIST-123');
        $this->get(route('admin.receipts.panel', $receipt))->assertSee('Read only')->assertSee('Historic soap')->assertDontSee('Archive receipt')->assertDontSee('Confirm release');
        $this->get(route('admin.receipts.slip', $receipt))->assertOk();
        Storage::disk('private')->assertExists('receipts/history.png');
        $this->post(route('admin.rewards.void', $reward), ['reason' => 'Attempt to change history'])->assertSessionHasErrors('error');
        $this->post(route('admin.rewards.release', $reward))->assertSessionHasErrors('error');
        $this->actingAs($customer)->post(route('customer.receipts.claim', $receipt))->assertSessionHasErrors('error');
        $this->assertSame('claimed', $reward->fresh()->claim_status);
    }

    public function test_terminal_receipts_without_rewards_can_be_archived(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['approved', 'rejected', 'cancelled'] as $status) {
            $receipt = Receipt::factory()->create(['status' => $status]);
            $this->post(route('admin.receipts.archive', $receipt))->assertSessionHasNoErrors();
            $this->assertNotNull($receipt->fresh()->archived_at);
        }
    }

    public function test_archived_rejected_order_number_can_be_resubmitted(): void
    {
        Storage::fake('private');
        $customer = User::factory()->create(['role' => 'customer']);
        $receipt = Receipt::factory()->create(['user_id' => $customer->id, 'status' => 'rejected', 'salesman_order_number' => 'ARCHIVE-REUSE']);
        Inventory::create(['name' => 'Laundry Soap', 'category' => 'home_care', 'stock_balance' => 20]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('admin.receipts.archive', $receipt))->assertSessionHasNoErrors();
        $this->actingAs($customer)->post(route('customer.submit_order'), [
            'salesman_order_number' => 'ARCHIVE-REUSE', 'order_date' => today()->toDateString(),
            'slip' => $this->fakePng('receipt.png'),
            'items' => [['product_name' => 'Laundry Soap', 'quantity' => 1]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('receipts', ['salesman_order_number' => 'ARCHIVE-REUSE', 'status' => 'pending']);
        $this->assertNotNull($receipt->fresh()->archived_at);
    }

    public function test_archive_tab_and_filters_persist_in_pagination_and_refresh_fragments(): void
    {
        Receipt::factory()->count(26)->create(['status' => 'approved', 'archived_at' => now()]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $params = ['archive' => 'archived', 'date_from' => today()->toDateString()];
        $this->get(route('admin.receipts', $params))->assertOk()->assertSee('archive=archived', false)->assertSee('date_from=', false);
        $this->withHeader('X-RTS-Fragment', 'lists')->get(route('admin.receipts', $params))->assertOk()->assertSee('name="archive" value="archived"', false)->assertSee('data-refresh-region="receipts"', false);
        $this->getJson(route('admin.receipts', ['archive' => 'archived', 'date_from' => '2026-02-10', 'date_to' => '2026-02-01']))->assertUnprocessable();
    }
}
