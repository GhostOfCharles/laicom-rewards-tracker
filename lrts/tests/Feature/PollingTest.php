<?php

namespace Tests\Feature;

use App\Models\CustomerNotification;
use App\Models\EarnedReward;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PollingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pulse_is_scoped_conditional_and_authenticated(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Receipt::factory()->create(['user_id' => $a->id]);
        Receipt::factory()->count(2)->create(['user_id' => $b->id]);
        CustomerNotification::create(['user_id' => $b->id, 'type' => 'secret', 'title' => 'PRIVATE', 'body' => 'Never expose', 'created_at' => now()]);
        $this->getJson(route('customer.pulse'))->assertUnauthorized();
        $response = $this->actingAs($a)->getJson(route('customer.pulse', ['user_id' => $b->id]))->assertOk()->assertJsonPath('pending_receipts', 1)->assertJsonPath('unread_notifications', 0)->assertDontSee('PRIVATE');
        $this->withHeader('If-None-Match', $response->headers->get('ETag'))->getJson(route('customer.pulse'))->assertStatus(304);
        $this->withHeader('If-None-Match', '')->getJson(route('admin.nav-counts'))->assertForbidden();
        Receipt::where('user_id', $a->id)->update(['status' => 'approved']);
        Cache::flush();
        $this->getJson(route('customer.pulse'))->assertJsonPath('pending_receipts', 0)->assertJsonPath('receipts.0.status', 'approved');
    }

    public function test_incremental_replies_exclude_notes_and_enforce_ownership(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::create(['user_id' => $customer->id, 'subject' => 'Question', 'body' => 'Please help with order', 'category' => 'orders', 'status' => 'open']);
        TicketReply::create(['ticket_id' => $ticket->id, 'user_id' => $admin->id, 'body' => 'Internal secret', 'is_internal_note' => true]);
        $public = TicketReply::create(['ticket_id' => $ticket->id, 'user_id' => $admin->id, 'body' => 'Public support response', 'is_internal_note' => false]);
        $this->actingAs($other)->getJson(route('customer.tickets.replies', $ticket))->assertForbidden();
        $this->actingAs($customer)->getJson(route('customer.tickets.replies', $ticket))->assertOk()->assertJsonCount(1, 'messages')->assertDontSee('Internal secret')->assertSee('Public support response');
        $this->getJson(route('customer.pulse'))->assertJsonPath('unseen_support_tickets', 1);
        $this->postJson(route('customer.tickets.read', $ticket), ['after' => $public->id])->assertOk();
        $this->getJson(route('customer.pulse'))->assertJsonPath('unseen_support_tickets', 0);
        $this->actingAs($admin)->getJson(route('admin.tickets.replies', $ticket))->assertOk()->assertJsonCount(2, 'messages');
        $this->getJson(route('admin.tickets.replies', [$ticket, 'after' => $public->id]))->assertJsonCount(0, 'messages');
    }

    public function test_customer_version_tracks_old_rewards_promotions_and_ticket_status(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $receipt = Receipt::factory()->create(['user_id' => $customer->id]);
        $promotion = Promotion::factory()->create();
        $rewards = EarnedReward::factory()->count(101)->create([
            'user_id' => $customer->id, 'receipt_id' => $receipt->id,
            'promotion_id' => $promotion->id, 'premium_product_id' => $promotion->premium_product_id,
        ]);
        $ticket = Ticket::create(['user_id' => $customer->id, 'subject' => 'Question', 'category' => 'orders', 'status' => 'open']);
        $this->actingAs($customer);
        $version = $this->getJson(route('customer.pulse'))->json('version');

        Receipt::factory()->create(['user_id' => $other->id]);
        Cache::forget('rts-pulse:customer:'.$customer->id);
        $this->assertSame($version, $this->getJson(route('customer.pulse'))->json('version'));

        $rewards->first()->update(['claim_status' => 'released']);
        Cache::forget('rts-pulse:customer:'.$customer->id);
        $next = $this->getJson(route('customer.pulse'))->json('version');
        $this->assertNotSame($version, $next);

        $promotion->update(['title' => 'Updated promotion']);
        Cache::forget('rts-pulse:customer:'.$customer->id);
        $version = $this->getJson(route('customer.pulse'))->json('version');
        $this->assertNotSame($next, $version);

        $ticket->update(['status' => 'closed']);
        Cache::forget('rts-pulse:customer:'.$customer->id);
        $this->assertNotSame($version, $this->getJson(route('customer.pulse'))->json('version'));
    }

    public function test_customer_dashboard_returns_current_claim_actions_and_is_scoped(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $receipt = Receipt::factory()->create(['user_id' => $customer->id, 'status' => 'approved']);
        $reward = EarnedReward::factory()->create(['user_id' => $customer->id, 'receipt_id' => $receipt->id, 'claim_status' => 'unclaimed']);
        Receipt::factory()->create(['user_id' => $other->id, 'salesman_order_number' => 'PRIVATE-OTHER-ORDER']);
        $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk()->assertSee('REQUEST CLAIM CODE')->assertDontSee('PRIVATE-OTHER-ORDER');

        $reward->update(['claim_status' => 'released']);
        $this->get(route('customer.dashboard'))->assertOk()->assertSee('I RECEIVED THESE REWARDS')->assertDontSee('PRIVATE-OTHER-ORDER');
    }

    public function test_notification_changes_update_the_customer_version_and_list_is_private(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($customer);
        $version = $this->getJson(route('customer.pulse'))->json('version');
        $notification = CustomerNotification::create(['user_id' => $customer->id, 'type' => 'test', 'title' => 'Your update', 'body' => 'A reward changed', 'created_at' => now()]);
        CustomerNotification::create(['user_id' => $other->id, 'type' => 'test', 'title' => 'Another customer secret', 'body' => 'Private', 'created_at' => now()]);
        Cache::forget('rts-pulse:customer:'.$customer->id);
        $response = $this->getJson(route('customer.pulse'))->assertJsonPath('unread_notifications', 1);
        $this->assertNotSame($version, $response->json('version'));
        $this->get(route('customer.notifications'))->assertOk()->assertSee('Your update')->assertDontSee('Another customer secret')->assertSee('data-customer-notifications', false);
        $notification->update(['read_at' => now()]);
        Cache::forget('rts-pulse:customer:'.$customer->id);
        $this->assertNotSame($response->json('version'), $this->getJson(route('customer.pulse'))->assertJsonPath('unread_notifications', 0)->json('version'));
    }

    public function test_admin_etag_and_poll_throttle_and_panel_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $receipt = Receipt::factory()->create();
        $this->actingAs($receipt->user)->get(route('admin.receipts.panel', $receipt))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.receipts.panel', $receipt))->assertOk()->assertSee('PURCHASED PRODUCTS');
        $response = $this->getJson(route('admin.nav-counts'))->assertOk()->assertJsonStructure(['receipts', 'claims', 'tickets', 'version']);
        $this->withHeader('If-None-Match', $response->headers->get('ETag'))->getJson(route('admin.nav-counts'))->assertStatus(304);
        for ($i = 0; $i < 58; $i++) {
            $this->getJson(route('admin.nav-counts'))->assertStatus(304);
        }
        $this->getJson(route('admin.nav-counts'))->assertStatus(429);
    }
}
