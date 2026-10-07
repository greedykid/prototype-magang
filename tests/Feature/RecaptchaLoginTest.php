<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan ada user admin
        User::factory()->create([
            'email' => 'admin@simasadi.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_login_proceeds_without_recaptcha_when_disabled(): void
    {
        config(['services.recaptcha.enabled' => false]);

        $response = $this->post(route('login.store'), [
            'email' => 'admin@simasadi.local',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_page_renders_recaptcha_widget_when_enabled(): void
    {
        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.site_key' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
        ]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('https://www.google.com/recaptcha/api.js?hl=id');
        $response->assertSee('g-recaptcha');
        $response->assertSee('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
    }

    public function test_login_fails_when_recaptcha_is_enabled_and_token_missing(): void
    {
        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.site_key' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
            'services.recaptcha.secret_key' => 'dummy_secret',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'admin@simasadi.local',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['g-recaptcha-response']);
        $this->assertGuest();
    }

    public function test_login_fails_when_google_siteverify_rejects_token(): void
    {
        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.site_key' => 'test_site_key',
            'services.recaptcha.secret_key' => 'test_secret_key',
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'admin@simasadi.local',
            'password' => 'password',
            'g-recaptcha-response' => 'fake_invalid_token',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['g-recaptcha-response']);
        $this->assertGuest();
    }

    public function test_login_succeeds_when_google_siteverify_accepts_token(): void
    {
        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.site_key' => 'test_site_key',
            'services.recaptcha.secret_key' => 'test_secret_key',
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'challenge_ts' => now()->toIso8601String(),
                'hostname' => 'simasadi.drzzy.my.id',
            ], 200),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'admin@simasadi.local',
            'password' => 'password',
            'g-recaptcha-response' => 'valid_mock_google_token',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }
}
