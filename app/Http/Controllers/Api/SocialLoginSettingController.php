<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SocialLoginSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'providers' => [
                'google' => $this->getProviderConfig('google'),
                'facebook' => $this->getProviderConfig('facebook'),
            ],
        ]);
    }

    /**
     * @return array<string, bool|string|null>
     */
    private function getProviderConfig(string $provider): array
    {
        $clientId = Setting::getValue("socialite.{$provider}.client_id");
        $clientSecret = Setting::getValue("socialite.{$provider}.client_secret");
        $redirect = Setting::getValue("socialite.{$provider}.redirect");

        return [
            'enabled' => filled($clientId) && filled($clientSecret) && filled($redirect),
            'client_id' => $clientId,
            'redirect' => $redirect,
        ];
    }
}
