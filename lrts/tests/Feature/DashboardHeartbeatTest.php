<?php

namespace Tests\Feature;

use App\Models\CustomerNotification;
use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DashboardHeartbeat;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeats_require_the_correct_authenticated_role(): void
    {
        $this->getJson(route('admin.heartbeat'))->assertUnauthorized();
        $this->getJson(route('customer.heartbeat'))->assertUnauthorized();
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($customer)->getJson(route('admin.heartbeat'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('customer.heartbeat'))->assertForbidden();
    }

    public function test_admin_counts_are_shared_cached_and_unchanged_heartbeats_have_no_body(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $this->receipt($customer);
        Ticket::create(['user_id' => $customer->id, 'subject' => 'Order help', 'category' => 'orders', 'status' => 'open']);
        PremiumProduct::create(['item_code' => 'LIVE-PERISHABLE', 'name' => 'Perishable reward', 'category' => 'home_care', 'stock' => 3, 'is_perishable' => true, 'entry_date' => today(), 'expiry_date' => today()->addDay()]);

        $response = $this->actingAs($admin)->getJson(route('admin.heartbeat'))->assertOk()
            ->assertJsonPath('counts.receipts', 1)->assertJsonPath('counts.tickets', 1)->assertJsonPath('counts.premium-expiry', 1);
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $etag = $response->headers->get('ETag');
        $this->getJson(route('admin.heartbeat'), ['If-None-Match' => $etag])->assertStatus(304)->assertContent('');
        $this->getJson(route('admin.nav-counts'))->assertExactJson($response->json('counts'));

        DB::enableQueryLog();
        DB::flushQueryLog();
        app(DashboardHeartbeat::class)->adminCounts();
        app(DashboardHeartbeat::class)->snapshot($admin);
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();

        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $otherResponse = $this->actingAs($otherAdmin)->getJson(route('admin.heartbeat'), ['If-None-Match' => $etag])->assertOk();
        $this->assertSame($response->json('counts'), $otherResponse->json('counts'));
        $this->assertNotSame($response->json('fingerprint'), $otherResponse->json('fingerprint'));
    }

    public function test_fingerprint_detects_same_second_status_changes_after_the_short_cache_expires(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $receipt = $this->receipt($user);
        $first = $this->actingAs($user)->getJson(route('customer.heartbeat'))->assertOk();
        $receipt->update(['status' => 'approved']);
        $this->getJson(route('customer.heartbeat'))->assertJsonPath('fingerprint', $first->json('fingerprint'));
        $this->travel(9)->seconds();
        $next = $this->getJson(route('customer.heartbeat'))->assertOk();
        $this->assertNotSame($first->json('fingerprint'), $next->json('fingerprint'));
        $this->assertSame(1, $next->json('versions.receipts.states.approved'));
    }

    public function test_customer_heartbeat_is_scoped_and_does_not_reveal_internal_notes(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->receipt($customer);
        $otherReceipt = $this->receipt($other);
        $ticket = Ticket::create(['user_id' => $customer->id, 'subject' => 'Reward help', 'category' => 'rewards', 'status' => 'pending']);
        $first = $this->actingAs($customer)->getJson(route('customer.heartbeat'))->assertOk()
            ->assertJsonPath('versions.receipts.count', 1)->assertJsonPath('counts.notifications', 0);
        $otherReceipt->update(['status' => 'approved']);
        $ticket->replies()->create(['user_id' => $admin->id, 'body' => 'Private internal note', 'is_internal_note' => true]);
        $this->travel(9)->seconds();
        $this->getJson(route('customer.heartbeat'))->assertJsonPath('fingerprint', $first->json('fingerprint'));

        $ticket->replies()->create(['user_id' => $admin->id, 'body' => 'Public support response', 'is_internal_note' => false]);
        CustomerNotification::create(['user_id' => $customer->id, 'type' => 'ticket.replied', 'title' => 'Support replied', 'body' => 'A reply is ready', 'created_at' => now()]);
        $this->travel(9)->seconds();
        $next = $this->getJson(route('customer.heartbeat'))->assertOk()
            ->assertJsonPath('versions.replies.count', 1)->assertJsonPath('counts.notifications', 1);
        $this->assertNotSame($first->json('fingerprint'), $next->json('fingerprint'));
        $next->assertDontSee('Public support response')->assertDontSee('Private internal note');
    }

    public function test_refresh_regions_protect_form_and_modal_nodes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $receipt = $this->receipt($customer);
        Inventory::create(['name' => 'Purchased soap', 'category' => 'home_care', 'stock_balance' => 20]);
        $premium = PremiumProduct::create(['item_code' => 'LIVE-MUG', 'name' => 'Reward mug', 'category' => 'home_care', 'stock' => 10]);
        $promotion = Promotion::create(['title' => 'Soap reward', 'buy_product_name' => 'Purchased soap', 'required_quantity' => 1, 'reward_quantity' => 1, 'premium_product_id' => $premium->id, 'is_active' => true, 'start_date' => today()->subDay(), 'end_date' => today()->addMonth()]);
        $approved = $receipt->replicate();
        $approved->fill(['salesman_order_number' => 'LIVE-APPROVED', 'status' => 'approved'])->save();
        EarnedReward::create(['user_id' => $customer->id, 'receipt_id' => $approved->id, 'promotion_id' => $promotion->id, 'premium_product_id' => $premium->id, 'reward_quantity' => 1, 'claim_status' => 'unclaimed']);
        Ticket::create(['user_id' => $customer->id, 'subject' => 'Reward help', 'category' => 'rewards', 'status' => 'open']);

        foreach (['admin.dashboard', 'admin.inventory', 'admin.receipts', 'admin.claims', 'admin.tickets', 'admin.reports', 'admin.activity-log', 'customer.dashboard'] as $route) {
            $user = str_starts_with($route, 'admin.') ? $admin : $customer;
            $html = $this->actingAs($user)->get(route($route, $route === 'admin.claims' ? ['tab' => 'available'] : []))->assertOk()->getContent();
            $document = new DOMDocument;
            $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);
            $regions = $xpath->query('//*[@data-live-region]');
            $this->assertGreaterThan(0, $regions->length, $route);
            $keys = [];
            foreach ($regions as $region) {
                $keys[] = $region->getAttribute('data-live-region');
                $this->assertSame(0, $xpath->query('.//script | .//*[contains(concat(" ", normalize-space(@class), " "), " modal ")]', $region)->length, $route);
                if ($region->hasAttribute('data-live-preserve-forms')) {
                    foreach ($xpath->query('.//form', $region) as $form) $this->assertNotSame('', $form->getAttribute('id'));
                    $this->assertSame(0, $xpath->query('.//input[not(ancestor::form)] | .//textarea[not(ancestor::form)] | .//select[not(ancestor::form)]', $region)->length, $route);
                } else {
                    $this->assertSame(0, $xpath->query('.//form | .//input | .//textarea | .//select', $region)->length, $route.' '.$region->getAttribute('data-live-region'));
                }
                $this->assertSame(0, $xpath->query('ancestor::form | ancestor::*[@data-live-region]', $region)->length, $route);
            }
            $this->assertSame($keys, array_values(array_unique($keys)), $route);
            foreach ($xpath->query('//button[@form]') as $button) {
                $this->assertSame(1, $xpath->query('//form[@id="'.$button->getAttribute('form').'"]')->length, $route.' must keep each action form available');
            }
        }
    }

    private function receipt(User $user): Receipt
    {
        return Receipt::create(['user_id' => $user->id, 'salesman_order_number' => 'LIVE-'.$user->id, 'status' => 'pending', 'order_date' => today(), 'submitted_at' => now()]);
    }
}
