<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class NotifyLkConfig
{
    /**
     * Map stored settings to Laravel service configuration values.
     */
    public static function apply(): void
    {
        $settings = Setting::cached();

        $map = [
            'services.notifylk.user_id' => 'notifylk.user_id',
            'services.notifylk.api_key' => 'notifylk.api_key',
            'services.notifylk.sender_id' => 'notifylk.sender_id',
            'services.notifylk.base_url' => 'notifylk.base_url',
        ];

        foreach ($map as $configKey => $settingKey) {
            if (array_key_exists($settingKey, $settings) && $settings[$settingKey] !== null) {
                Config::set($configKey, $settings[$settingKey]);
            }
        }
    }
}
