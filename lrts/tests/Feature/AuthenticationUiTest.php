<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use EduLazaro\Laracaptcha\Facades\Captcha as CaptchaFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticationUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_authentication_pages_keep_accessible_fields_and_original_form_routes(): void
    {
        $pages = [
            ['portal', 'Welcome Back', null, []],
            ['login.customer', 'Welcome Back', 'login.customer.submit', ['email' => 'email', 'password' => 'password']],
            ['login.admin', 'Admin Login', 'login.admin.submit', ['email' => 'email', 'password' => 'password']],
            ['register.customer', 'Create an Account', 'register.customer.submit', [
                'name' => 'text', 'email' => 'email', 'store_name' => 'text',
                'phone_number' => 'text', 'password' => 'password', 'password_confirmation' => 'password',
            ]],
        ];

        foreach ($pages as [$routeName, $heading, $submitRoute, $fields]) {
            $response = $this->get(route($routeName))->assertOk()
                ->assertSeeText($heading)
                ->assertSeeText('Manage rewards. Track progress.')
                ->assertDontSee('LAICOM')
                ->assertDontSee('REWARDS TODAY');

            if (! $submitRoute) {
                continue;
            }

            $document = new DOMDocument;
            $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);
            $form = $xpath->query('//form')->item(0);
            $this->assertNotNull($form);
            $this->assertSame(route($submitRoute), $form->getAttribute('action'));
            $this->assertSame('POST', strtoupper($form->getAttribute('method')));
            $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $form)->length);

            foreach ($fields as $name => $type) {
                $input = $xpath->query('.//input[@name="' . $name . '"]', $form)->item(0);
                $this->assertNotNull($input, $routeName . ' must include ' . $name);
                $this->assertSame($type, $input->getAttribute('type'));
                $this->assertTrue($input->hasAttribute('required'));
                $this->assertSame(1, $xpath->query('//label[@for="' . $input->getAttribute('id') . '"]')->length);
            }
        }
    }

    public function test_customer_can_still_sign_in_through_the_customer_form(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->post(route('login.customer.submit'), [
            'email' => $customer->email, 'password' => 'password',
        ])->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_admin_can_still_sign_in_through_the_admin_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('login.admin.submit'), [
            'email' => $admin->email, 'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_customer_cannot_sign_in_through_the_admin_form(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->from(route('login.admin'))->post(route('login.admin.submit'), [
            'email' => $customer->email, 'password' => 'password',
        ])->assertRedirect(route('login.admin'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_failed_sign_in_preserves_email_and_displays_accessible_validation_feedback(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->from(route('login.customer'))->post(route('login.customer.submit'), [
            'email' => $customer->email, 'password' => 'incorrect-password',
        ])->assertRedirect(route('login.customer'))->assertSessionHasErrors('email');

        $this->get(route('login.customer'))->assertOk()
            ->assertSee('The provided credentials do not match our records.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('value="' . $customer->email . '"', false)
            ->assertDontSee('incorrect-password');

        $this->assertGuest();
    }

    public function test_registration_still_rejects_missing_or_invalid_security_verification(): void
    {
        config(['laracaptcha.default' => 'turnstile']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false], 200)]);
        $fields = [
            'name' => 'New Customer', 'email' => 'captcha-check@example.test',
            'store_name' => 'Test Store', 'phone_number' => '09171234567',
            'password' => 'C0mpl3x!Pass', 'password_confirmation' => 'C0mpl3x!Pass',
        ];
        $responseField = CaptchaFacade::responseField();

        $this->post(route('register.customer.submit'), $fields)->assertSessionHasErrors($responseField);
        $this->post(route('register.customer.submit'), $fields + [$responseField => 'invalid-captcha'])
            ->assertSessionHasErrors($responseField);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }
}
