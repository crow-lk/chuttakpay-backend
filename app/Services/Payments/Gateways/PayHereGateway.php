<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\PaymentNotification;
use RuntimeException;

class PayHereGateway implements PaymentGatewayContract
{
    private const SANDBOX_URL = 'https://sandbox.payhere.lk/pay/checkout';

    private const LIVE_URL = 'https://www.payhere.lk/pay/checkout';

    public function prepareCheckout(Payment $payment, array $context = []): array
    {
        $payment->loadMissing('paymentMethod');
        $paymentMethod = $payment->paymentMethod;
        $settings = $paymentMethod->settings ?? [];

        $merchantId = $settings['merchant_id'] ?? null;
        $merchantSecret = $settings['merchant_secret'] ?? null;

        if (blank($merchantId) || blank($merchantSecret)) {
            throw new RuntimeException('PayHere merchant credentials are not configured.');
        }

        $reference = $payment->reference_number ?? 'PAY-'.$payment->id;
        if ($payment->reference_number !== $reference) {
            $payment->forceFill(['reference_number' => $reference])->save();
        }

        $currency = $context['currency'] ?? $settings['currency'] ?? 'LKR';
        $amount = number_format((float) $payment->amount_paid, 2, '.', '');

        $hashedSecret = strtoupper(md5($merchantSecret));
        $hash = strtoupper(md5($merchantId.$reference.$amount.$currency.$hashedSecret));

        $customer = $context['customer'] ?? [];
        $itemsLabel = $context['items'] ?? ('Order '.$reference);

        $returnUrl = $context['return_url'] ?? $settings['return_url'] ?? url('/');
        $cancelUrl = $context['cancel_url'] ?? $settings['cancel_url'] ?? $returnUrl;
        $notifyUrl = $context['notify_url'] ?? $settings['notify_url'] ?? $returnUrl;

        $sandboxSetting = $settings['sandbox'] ?? true;
        $useSandbox = filter_var($sandboxSetting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $useSandbox ??= (bool) $sandboxSetting;

        return [
            'type' => 'redirect',
            'action_url' => $useSandbox ? self::SANDBOX_URL : self::LIVE_URL,
            'fields' => [
                'merchant_id' => $merchantId,
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'notify_url' => $notifyUrl,
                'order_id' => $reference,
                'items' => $itemsLabel,
                'currency' => $currency,
                'amount' => $amount,
                'first_name' => $customer['first_name'] ?? '',
                'last_name' => $customer['last_name'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => $customer['phone'] ?? '',
                'address' => $customer['address'] ?? '',
                'city' => $customer['city'] ?? '',
                'country' => $customer['country'] ?? 'Sri Lanka',
                'hash' => $hash,
            ],
        ];
    }

    public function verifyNotification(array $payload, PaymentMethod $paymentMethod): PaymentNotification
    {
        $settings = $paymentMethod->settings ?? [];
        $merchantId = $settings['merchant_id'] ?? null;
        $merchantSecret = $settings['merchant_secret'] ?? null;

        if (blank($merchantId) || blank($merchantSecret)) {
            throw new RuntimeException('PayHere merchant credentials are not configured.');
        }

        $statusCode = (int) ($payload['status_code'] ?? 0);

        $expected = strtoupper(md5(
            ($merchantId ?? '').
            ($payload['order_id'] ?? '').
            ($payload['payhere_amount'] ?? '').
            ($payload['payhere_currency'] ?? '').
            $statusCode.
            strtoupper(md5($merchantSecret))
        ));

        $verified = hash_equals($expected, strtoupper((string) ($payload['md5sig'] ?? '')));

        return new PaymentNotification(
            verified: $verified,
            status: $this->mapStatusCode($statusCode),
            data: $payload,
            reference: $payload['payment_id'] ?? null
        );
    }

    private function mapStatusCode(int $statusCode): string
    {
        return match ($statusCode) {
            2 => 'paid',
            0 => 'pending',
            -1 => 'cancelled',
            -2 => 'failed',
            -3 => 'chargedback',
            default => 'pending',
        };
    }
}
