<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLoginSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_social_login_configuration_for_providers(): void
    {
        Setting::setValue('socialite.google.client_id', 'google-client');
        Setting::setValue('socialite.google.client_secret', 'test-secret');
        Setting::setValue('socialite.google.redirect', 'https://example.com/google/callback');

        $response = $this->getJson('/api/settings/social-login');

        $response->assertOk()
            ->assertJson([
                'providers' => [
                    'google' => [
                        'enabled' => true,
                        'client_id' => 'google-client',
                        'redirect' => 'https://example.com/google/callback',
                    ],
                    'facebook' => [
                        'enabled' => false,
                        'client_id' => null,
                        'redirect' => null,
                    ],
                ],
            ]);
    }

    public function test_provider_is_disabled_if_client_secret_is_missing(): void
    {
        Setting::setValue('socialite.google.client_id', 'google-client');
        Setting::setValue('socialite.google.redirect', 'https://example.com/google/callback');

        $response = $this->getJson('/api/settings/social-login');

        $response->assertOk()
            ->assertJsonPath('providers.google.enabled', false);
    }
}
