<?php

namespace Tests\Feature;

use App\Models\CustomerNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_profile_but_password_requires_current_secret(): void
    {
        $user = User::factory()->create(['password' => 'Original_Password9']);
        $this->actingAs($user)->patch(route('customer.profile.update'), ['name' => 'Customer', 'store_name' => 'New Store', 'phone_number' => '0912 345 6789'])->assertSessionHasNoErrors();
        $this->assertSame('09123456789', $user->fresh()->phone_number);
        $this->put(route('customer.password'), ['current_password' => 'wrong', 'password' => 'New_Password9', 'password_confirmation' => 'New_Password9'])->assertSessionHasErrors('current_password');
        $user->tokens()->create(['name' => 'test', 'token' => hash('sha256', 'test'), 'abilities' => ['*']]);
        $this->put(route('customer.password'), ['current_password' => 'Original_Password9', 'password' => 'New_Password9', 'password_confirmation' => 'New_Password9'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('New_Password9', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_notification_ownership_and_temporary_password_gate(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = CustomerNotification::create(['user_id' => $other->id, 'type' => 'test', 'title' => 'Private', 'body' => 'Other customer', 'created_at' => now()]);
        $this->actingAs($user)->post(route('customer.notifications.item.read', $notification))->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put(route('admin.customers.password', $user), ['password' => 'Temporary_Password9', 'password_confirmation' => 'Temporary_Password9'])->assertSessionHasNoErrors();
        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect(route('customer.profile'));
        $this->get(route('customer.profile'))->assertOk();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_all_admin_and_customer_pages_render_with_local_assets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['admin.dashboard', 'admin.inventory', 'admin.receipts', 'admin.claims', 'admin.tickets', 'admin.reports', 'admin.activity-log', 'admin.customers'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk()->assertSee('css/rts.css')->assertDontSee('cdn.jsdelivr.net');
        }
        $user = User::factory()->create();
        foreach (['customer.dashboard', 'customer.profile', 'customer.notifications'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertDontSee('cdn.jsdelivr.net');
        }
    }
}
