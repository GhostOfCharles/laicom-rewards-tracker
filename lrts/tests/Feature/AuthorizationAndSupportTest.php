<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\Inventory;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthorizationAndSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_are_restricted_and_customer_cannot_read_internal_ticket_notes(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login.admin'));

        $customer = User::factory()->create(['role' => 'customer']);
        $ticket = Ticket::create([
            'user_id' => $customer->id,
            'subject' => 'Missing reward',
            'category' => 'rewards',
            'status' => 'open',
            'priority' => 'normal',
        ]);
        $ticket->replies()->create([
            'user_id' => $customer->id,
            'body' => 'I need help with my approved reward.',
            'is_internal_note' => false,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket->replies()->create([
            'user_id' => $admin->id,
            'body' => 'Private admin review note.',
            'is_internal_note' => true,
        ]);

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('customer.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('I need help with my approved reward.')
            ->assertDontSee('Private admin review note.');
    }

    public function test_customer_support_ticket_attachment_upload_is_saved_and_owner_can_reply(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->post(route('customer.tickets.store'), [
            'subject' => 'Order status question',
            'category' => 'orders',
            'body' => 'Please check the status of my latest order.',
            'attachment' => UploadedFile::fake()->createWithContent('order.png', file_get_contents(public_path('images/laicom-logo.png'))),
        ])->assertRedirect();

        $ticket = Ticket::where('user_id', $customer->id)->firstOrFail();
        $reply = $ticket->replies()->firstOrFail();
        Storage::disk('public')->assertExists($reply->attachment_path);

        $this->post(route('customer.tickets.reply', $ticket), [
            'body' => 'Thank you, I have more information to add.',
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'body' => 'Thank you, I have more information to add.',
        ]);
    }

    public function test_api_login_and_receipt_submission_resolve_to_the_api_controllers(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        Inventory::create(['name' => 'Dish Soap', 'category' => 'home_care', 'stock_balance' => 10]);

        $login = $this->postJson('/api/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('message', 'Login successful.');

        $token = $login->json('token');
        $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/receipts', [
            'salesman_order_number' => 'API-ORDER-1',
            'items' => [['product_name' => 'Dish Soap', 'quantity' => 2]],
        ])->assertCreated();

        $this->assertDatabaseHas('receipts', [
            'salesman_order_number' => 'API-ORDER-1',
            'user_id' => $customer->id,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = $admin->createToken('test')->plainTextToken;
        // The test application reuses guards between requests; clear Sanctum's cached customer.
        Auth::forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $adminToken)->postJson('/api/receipts', [
            'salesman_order_number' => 'API-ADMIN-ORDER',
            'items' => [['product_name' => 'Dish Soap', 'quantity' => 2]],
        ])->assertForbidden();

        $this->assertDatabaseMissing('receipts', ['salesman_order_number' => 'API-ADMIN-ORDER']);
    }
}
