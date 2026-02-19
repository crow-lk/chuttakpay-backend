<?php

namespace App\Providers;

use App\Services\Payments\Gateways\ManualGateway;
use App\Services\Payments\Gateways\MintpayGateway;
use App\Services\Payments\Gateways\PayHereGateway;
use App\Services\Payments\PaymentGatewayManager;
use App\Support\NotifyLkConfig;
use App\Support\SocialiteConfig;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function () {
            return new PaymentGatewayManager([
                'manual' => ManualGateway::class,
                'manual_bank' => ManualGateway::class,
                'cod' => ManualGateway::class,
                'payhere' => PayHereGateway::class,
                'koko' => ManualGateway::class,
                'mintpay' => MintpayGateway::class,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->canLoadSettings()) {
            SocialiteConfig::apply();
            NotifyLkConfig::apply();
        }
    }

    private function canLoadSettings(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }
}
