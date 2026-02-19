<?php

namespace App\Services\Payments\Contracts;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payments\PaymentNotification;

interface PaymentGatewayContract
{
    /**
     * Prepare the checkout payload that the frontend can use to continue the payment flow.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function prepareCheckout(Payment $payment, array $context = []): array;

    /**
     * Verify the notification payload received from the gateway.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyNotification(array $payload, PaymentMethod $paymentMethod): PaymentNotification;
}
