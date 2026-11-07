<?php

namespace Tests\Feature;

use App\Exports\ReportTableExport;
use App\Models\Inventory;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalized_api_product_name_and_duplicate_attempt_audit(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $item = Inventory::factory()->create(['name' => 'Shampoo 1.5L']);
        $token = $user->createToken('test', ['receipts:create'])->plainTextToken;
        $payload = ['salesman_order_number' => 'API-NORMALIZED', 'order_date' => today()->toDateString(), 'slip' => $this->fakePng('receipt.png'), 'items' => [['product_name' => '  SHAMPOO   1.5L  ', 'quantity' => 2]]];
        $this->withToken($token)->postJson('/api/receipts', $payload)->assertCreated();
        $this->assertDatabaseHas('receipt_items', ['inventory_id' => $item->id, 'product_name' => $item->name]);
        $this->withToken($token)->postJson('/api/receipts', $payload)->assertUnprocessable();
        $this->assertDatabaseHas('activity_logs', ['action' => 'receipt.duplicate_blocked', 'user_id' => $user->id]);
        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_expired_and_wrong_ability_tokens_cannot_read_the_api(): void
    {
        $user = User::factory()->create();
        $expired = $user->createToken('expired', ['user:read'], now()->subDay())->plainTextToken;
        $this->withToken($expired)->getJson('/api/user')->assertUnauthorized();
        Auth::forgetGuards();
        $limited = $user->createToken('limited', ['receipts:create'])->plainTextToken;
        $this->withToken($limited)->getJson('/api/user')->assertForbidden();
    }

    public function test_mail_recovery_resets_password_and_revokes_existing_tokens(): void
    {
        config(['lrts.password_reset_enabled' => true, 'mail.default' => 'smtp']);
        Notification::fake();
        $user = User::factory()->create();
        $user->createToken('old');
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect()->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPassword::class);
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'Recovered_Password9', 'password_confirmation' => 'Recovered_Password9'];
        $this->post(route('password.update'), $payload)->assertRedirect(route('login.customer'));
        $this->assertTrue(Hash::check($payload['password'], $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');
    }

    public function test_bracketed_product_choices_use_indexed_selections(): void
    {
        $item = Inventory::factory()->create(['name' => 'Shampoo [1.5L]']);
        $promo = Promotion::factory()->create(['buy_product_name' => $item->name]);
        Promotion::factory()->create(['buy_product_name' => $item->name]);
        $receipt = Receipt::factory()->create();
        $receipt->items()->create(['product_name' => $item->name, 'quantity' => 2, 'unit_price' => 0]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('admin.receipts.approve', $receipt), ['selections' => [0 => $promo->id]])->assertSessionHasNoErrors();
        $this->assertSame('approved', $receipt->fresh()->status);
    }

    public function test_list_fragments_are_small_and_receipt_queries_do_not_scale_per_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $receipt = Receipt::factory()->create();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($admin)->withHeader('X-RTS-Fragment', 'lists')->get(route('admin.receipts'))->assertOk()->assertDontSee('<html', false);
        $small = count(DB::getQueryLog());
        Receipt::factory()->count(30)->create();
        DB::flushQueryLog();
        $this->get(route('admin.receipts'))->assertOk();
        $this->assertLessThanOrEqual($small + 2, count(DB::getQueryLog()));
        DB::disableQueryLog();
        $this->actingAs($receipt->user)->get(route('customer.dashboard'))->assertOk()->assertSee('data-refresh-region="tracker"', false)->assertDontSee('<html', false);
    }

    public function test_export_strings_cannot_become_spreadsheet_formulas(): void
    {
        $export = new ReportTableExport([['=HYPERLINK("bad")', '+SUM(1,1)', 12]]);
        $this->assertSame([["'=HYPERLINK(\"bad\")", "'+SUM(1,1)", 12]], $export->array());
    }

    public function test_private_pdf_is_downloaded_and_malformed_login_is_validated(): void
    {
        Storage::fake('private');
        $customer = User::factory()->create();
        $ticket = Ticket::create(['user_id' => $customer->id, 'subject' => 'PDF question', 'category' => 'orders', 'body' => 'Check this attachment', 'status' => 'open']);
        $reply = $ticket->replies()->create(['user_id' => $customer->id, 'body' => 'Private PDF', 'is_internal_note' => false, 'attachment_path' => 'ticket-attachments/test.pdf']);
        Storage::disk('private')->put($reply->attachment_path, "%PDF-1.4\n");
        $this->actingAs($customer)->get(route('customer.tickets.attachment', $reply))->assertDownload('attachment.pdf');
        $this->actingAs(User::factory()->create())->get(route('customer.tickets.attachment', $reply))->assertForbidden();
        Auth::forgetGuards();
        $this->postJson('/login', ['email' => ['invalid'], 'password' => ['invalid']])->assertUnprocessable();
    }
}
