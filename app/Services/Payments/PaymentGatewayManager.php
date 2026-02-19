<?php

namespace App\Services\Payments;

use App\Models\PaymentMethod;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @param  array<string, class-string<PaymentGatewayContract>>  $gateways
     */
    public function __construct(private readonly array $gateways = []) {}

    public function forMethod(PaymentMethod $paymentMethod): PaymentGatewayContract
    {
        $driver = $paymentMethod->gateway ?? 'manual';

        return $this->driver($driver);
    }

    public function driver(string $driver): PaymentGatewayContract
    {
        $map = $this->gateways;

        if (! array_key_exists($driver, $map)) {
            throw new InvalidArgumentException("Payment gateway [{$driver}] is not supported.");
        }

        return app($map[$driver]);
    }
}
