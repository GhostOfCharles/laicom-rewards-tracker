<?php

namespace Tests\Feature;

use App\Models\User;
use EduLazaro\Laracaptcha\Facades\Captcha as CaptchaFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerRegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_has_live_password_and_phone_feedback(): void
    {
        $this->get(route('register.customer'))
            ->assertOk()
            ->assertSee('id="pwStrength"', false)
            ->assertSee('id="pwToggle"', false)
            ->assertSee('id="pwMatch"', false)
            ->assertSee('placeholder="09XX XXX XXXX"', false)
            ->assertSee('formatPhone', false);
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $this->fakeCaptchaProvider();

        $this->postRegistration(['password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_accepts_a_strong_password(): void
    {
        $this->fakeCaptchaProvider();

        $this->postRegistration(['password' => 'C0mpl3x!Pass', 'password_confirmation' => 'C0mpl3x!Pass'])
            ->assertRedirect(route('customer.dashboard'));

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.pwnedpasswords.com'));
        $this->assertDatabaseHas('users', ['email' => 'register@example.test', 'role' => 'customer']);
        $this->assertTrue(password_verify('C0mpl3x!Pass', User::where('email', 'register@example.test')->value('password')));
    }

    public function test_registration_normalizes_phone_numbers_and_stores_digits_only(): void
    {
        $this->fakeCaptchaProvider();

        foreach (['09171234567', '0917 123 4567'] as $index => $phone) {
            $email = 'phone' . $index . '@example.test';
            $this->postRegistration([
                'email' => $email,
                'phone_number' => $phone,
                'password' => 'C0mpl3x!Pass',
                'password_confirmation' => 'C0mpl3x!Pass',
            ])->assertRedirect(route('customer.dashboard'));

            $this->assertDatabaseHas('users', ['email' => $email, 'phone_number' => '09171234567']);
        }
    }

    public function test_registration_rejects_invalid_phone_lengths_and_prefixes(): void
    {
        $this->fakeCaptchaProvider();

        foreach (['12345', '0917123456', '08171234567'] as $index => $phone) {
            $this->postRegistration([
                'email' => 'invalid' . $index . '@example.test',
                'phone_number' => $phone,
                'password' => 'C0mpl3x!Pass',
                'password_confirmation' => 'C0mpl3x!Pass',
            ])->assertSessionHasErrors('phone_number');
        }

        $this->assertDatabaseCount('users', 0);
    }

    private function postRegistration(array $overrides = [])
    {
        return $this->post(route('register.customer.submit'), array_merge([
            'name' => 'Test Customer',
            'email' => 'register@example.test',
            'store_name' => 'Test Store',
            'phone_number' => '09171234567',
            'password' => 'C0mpl3x!Pass',
            'password_confirmation' => 'C0mpl3x!Pass',
            CaptchaFacade::responseField() => 'test-captcha-' . Str::uuid(),
        ], $overrides));
    }

    private function fakeCaptchaProvider(): void
    {
        config(['laracaptcha.default' => 'turnstile']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true], 200)]);
    }
}
