<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\PaymentNotification;

class ManualGateway implements PaymentGatewayContract
{
    public function prepareCheckout(Payment $payment, array $context = []): array
    {
        $paymentMethod = $payment->paymentMethod;

        return [
            'type' => 'manual',
            'payment_method_id' => $paymentMethod->id,
            'instructions' => $paymentMethod->instructions,
            'details' => $paymentMethod->settings ?? [],
        ];
    }

    public function verifyNotification(array $payload, PaymentMethod $paymentMethod): PaymentNotification
    {
        return new PaymentNotification(
            verified: false,
            status: 'pending',
            data: $payload
        );
    }
}
