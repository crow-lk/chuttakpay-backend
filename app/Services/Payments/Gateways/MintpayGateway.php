<?php

namespace App\Services\Payments\Gateways;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\PaymentNotification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MintpayGateway implements PaymentGatewayContract
{
    private const SANDBOX_API_URL = 'https://dev.mintpay.lk/user-order/api/';

    private const LIVE_API_URL = 'https://app.mintpay.lk/user-order/api/';

    private const SANDBOX_LOGIN_URL = 'https://dev.mintpay.lk/user-order/login/';

    private const LIVE_LOGIN_URL = 'https://app.mintpay.lk/user-order/login/';

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function prepareCheckout(Payment $payment, array $context = []): array
    {
        $payment->loadMissing(['paymentMethod', 'cart']);
        $paymentMethod = $payment->paymentMethod;

        $settings = $paymentMethod->settings ?? [];
        $merchantId = $settings['merchant_id'] ?? null;
        $token = $this->resolveToken($settings);

        if (blank($merchantId) || blank($token)) {
            throw new RuntimeException('Mintpay merchant credentials are not configured.');
        }

        $orderId = $payment->reference_number ?? 'PAY-'.$payment->id;
        if ($payment->reference_number !== $orderId) {
            $payment->forceFill(['reference_number' => $orderId])->save();
        }

        $cart = $context['cart'] ?? $payment->cart;

        if (! $cart) {
            throw new RuntimeException('Mintpay requires a cart to initialize checkout.');
        }

        $cart->loadMissing($this->cartRelations());

        $customer = $context['customer'] ?? [];
        $shipping = $context['shipping'] ?? [];

        $deliveryStreet = trim(implode(' ', array_filter([
            $shipping['address_line1'] ?? null,
            $shipping['address_line2'] ?? null,
        ])));
        $deliveryStreet = $deliveryStreet !== '' ? $deliveryStreet : (string) ($customer['address'] ?? '');

        $deliveryRegion = (string) ($shipping['city'] ?? $customer['city'] ?? '');
        $deliveryPostcode = (string) ($shipping['postal_code'] ?? $customer['postal_code'] ?? $customer['postcode'] ?? '');

        $successUrl = (string) (
            $context['success_url']
            ?? $context['return_url']
            ?? $settings['success_url']
            ?? $settings['return_url']
            ?? url('/')
        );
        $failUrl = (string) (
            $context['fail_url']
            ?? $context['cancel_url']
            ?? $settings['fail_url']
            ?? $settings['cancel_url']
            ?? $successUrl
        );

        $payload = [
            'merchant_id' => (string) $merchantId,
            'order_id' => (string) $orderId,
            'total_price' => $this->formatAmount($payment->amount_paid),
            'discount' => $this->formatAmount($cart->discount_total ?? 0),
            'customer_email' => (string) ($customer['email'] ?? ''),
            'customer_id' => (string) ($context['customer_id'] ?? $context['user_id'] ?? $context['user']?->id ?? ''),
            'customer_telephone' => (string) ($customer['phone'] ?? ''),
            'ip' => (string) ($context['ip'] ?? ''),
            'x_forwarded_for' => (string) ($context['x_forwarded_for'] ?? ''),
            'delivery_street' => $deliveryStreet,
            'delivery_region' => $deliveryRegion,
            'delivery_postcode' => $deliveryPostcode,
            'cart_created_date' => $this->formatDate($cart->created_at),
            'cart_updated_date' => $this->formatDate($cart->updated_at),
            'products' => $this->buildProducts($cart),
            'success_url' => $successUrl,
            'fail_url' => $failUrl,
        ];

        $useSandbox = $this->usesSandbox($settings);
        $response = Http::withHeaders([
            'Authorization' => 'Token '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($useSandbox ? self::SANDBOX_API_URL : self::LIVE_API_URL, $payload);

        $responseData = $response->json() ?? [];

        if (! $response->ok() || ($responseData['message'] ?? '') !== 'Success') {
            $reason = (string) ($responseData['data'] ?? $responseData['message'] ?? 'Mintpay request failed.');
            throw new RuntimeException('Mintpay request failed: '.$reason);
        }

        $purchaseId = (string) ($responseData['data'] ?? '');

        if ($purchaseId === '') {
            throw new RuntimeException('Mintpay did not return a purchase id.');
        }

        return [
            'type' => 'redirect',
            'action_url' => $useSandbox ? self::SANDBOX_LOGIN_URL : self::LIVE_LOGIN_URL,
            'fields' => [
                'purchase_id' => $purchaseId,
            ],
            'purchase_id' => $purchaseId,
            'order_id' => (string) $orderId,
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildProducts(Cart $cart): array
    {
        return $cart->items
            ->map(function (CartItem $item): array {
                $variant = $item->variant;
                $product = $variant?->product;
                $createdAt = $product?->created_at ?? $variant?->created_at;
                $updatedAt = $product?->updated_at ?? $variant?->updated_at;

                return [
                    'name' => (string) ($product?->name ?? $variant?->display_name ?? 'Product'),
                    'product_id' => (string) ($product?->id ?? $variant?->id ?? ''),
                    'sku' => $this->buildVariantSku($variant),
                    'quantity' => (int) ($item->quantity ?? 0),
                    'unit_price' => $this->formatAmount($item->unit_price),
                    'discount' => $this->formatAmount(0),
                    'created_date' => $this->formatDate($createdAt),
                    'updated_date' => $this->formatDate($updatedAt),
                ];
            })
            ->values()
            ->all();
    }

    private function buildVariantSku(?ProductVariant $variant): string
    {
        if (! $variant) {
            return '';
        }

        if (filled($variant->sku)) {
            return (string) $variant->sku;
        }

        $parts = array_filter([
            $variant->size?->name,
            $variant->color_names,
        ]);

        return $parts === [] ? '' : implode(' / ', $parts);
    }

    private function formatAmount(mixed $amount): string
    {
        return number_format((float) $amount, 4, '.', '');
    }

    private function formatDate(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        return now()->format('Y-m-d H:i:s');
    }

    /**
     * @return list<string>
     */
    private function cartRelations(): array
    {
        return [
            'items.variant',
            'items.variant.product',
            'items.variant.colors',
            'items.variant.size',
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function usesSandbox(array $settings): bool
    {
        $sandboxSetting = $settings['sandbox'] ?? true;
        $useSandbox = filter_var($sandboxSetting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $useSandbox ??= (bool) $sandboxSetting;

        return $useSandbox;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function resolveToken(array $settings): ?string
    {
        return $settings['token']
            ?? $settings['secret_key']
            ?? $settings['merchant_secret']
            ?? $settings['api_token']
            ?? null;
    }
}
