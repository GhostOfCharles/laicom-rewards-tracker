<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokens_are_bounded_rotated_and_admin_login_is_refused(): void
    {
        $user = User::factory()->create(['password' => 'StrongPassword_123']);
        $user->tokens()->create(['name' => 'flutter-mobile-app', 'token' => hash('sha256', 'old'), 'abilities' => ['*']]);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'StrongPassword_123'])->assertOk()->assertJsonStructure(['token', 'user', 'message']);
        $token = $user->tokens()->sole();
        $this->assertSame(['receipts:create', 'user:read'], $token->abilities);
        $this->assertNotNull($token->expires_at);
        $admin = User::factory()->create(['role' => 'admin', 'password' => 'StrongPassword_123']);
        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'StrongPassword_123'])->assertForbidden();
        $this->assertSame(86400, config('sanctum.expiration'));
    }

    public function test_registration_and_order_limits_apply_to_invalid_requests_too(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/register', [])->assertUnprocessable();
        }
        $this->postJson('/register', [])->assertStatus(429);
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/dashboard/submit-order', [])->assertUnprocessable();
        }
        $this->postJson('/dashboard/submit-order', [])->assertStatus(429);
    }

    public function test_login_requires_captcha_after_three_failures_and_has_limit(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/login', ['email' => 'invalid@example.com', 'password' => 'bad'])->assertRedirect();
        }
        $this->postJson('/login', ['email' => 'invalid@example.com', 'password' => 'bad'])->assertUnprocessable()->assertJsonValidationErrors('cf-turnstile-response');
        $this->postJson('/login', ['email' => 'invalid@example.com', 'password' => 'bad']);
        $this->postJson('/login', ['email' => 'invalid@example.com', 'password' => 'bad'])->assertStatus(429);
    }

    public function test_oversized_image_is_rejected_before_gd_decoding(): void
    {
        $item = Inventory::factory()->create();
        $image = $this->fakePng('huge.png');
        $bytes = file_get_contents($image->getRealPath());
        $bytes = substr_replace($bytes, pack('N', 10000).pack('N', 10000), 16, 8);
        file_put_contents($image->getRealPath(), $bytes);
        $this->actingAs(User::factory()->create())->postJson('/dashboard/submit-order', [
            'salesman_order_number' => 'HUGE-1', 'order_date' => today()->toDateString(), 'slip' => $image,
            'items' => [['product_name' => $item->name, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('slip');
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_production_seeder_never_creates_predictable_accounts(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 0);
    }
}
