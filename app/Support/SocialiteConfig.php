<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class SocialiteConfig
{
    /**
     * Map stored settings to Laravel service configuration values.
     */
    public static function apply(): void
    {
        $settings = Setting::cached();

        $map = [
            'services.google.client_id' => 'socialite.google.client_id',
            'services.google.client_secret' => 'socialite.google.client_secret',
            'services.google.redirect' => 'socialite.google.redirect',
            'services.facebook.client_id' => 'socialite.facebook.client_id',
            'services.facebook.client_secret' => 'socialite.facebook.client_secret',
            'services.facebook.redirect' => 'socialite.facebook.redirect',
        ];

        foreach ($map as $configKey => $settingKey) {
            if (array_key_exists($settingKey, $settings) && $settings[$settingKey] !== null) {
                Config::set($configKey, $settings[$settingKey]);
            }
        }
    }
}
