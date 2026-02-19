<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MintpayStatusController extends Controller
{
    private const SANDBOX_STATUS_URL = 'https://dev.mintpay.lk/user-order/api/status/merchantId/';

    private const LIVE_STATUS_URL = 'https://app.mintpay.lk/user-order/api/status/merchantId/';

    public function __invoke(Request $request, Payment $payment): JsonResponse
    {
        $payment->loadMissing(['paymentMethod', 'order']);
        $paymentMethod = $payment->paymentMethod;

        if (! $paymentMethod || $paymentMethod->gateway !== 'mintpay') {
            return response()->json(['message' => 'Payment is not a Mintpay payment.'], 422);
        }

        $settings = $paymentMethod->settings ?? [];
        $merchantId = $settings['merchant_id'] ?? null;
        $token = $this->resolveToken($settings);

        if (blank($merchantId) || blank($token)) {
            return response()->json(['message' => 'Mintpay merchant credentials are not configured.'], 422);
        }

        $purchaseId = $request->string('purchase_id')->toString();
        if ($purchaseId === '') {
            $purchaseId = (string) data_get($payment->gateway_payload, 'purchase_id');
        }

        if ($purchaseId === '') {
            return response()->json(['message' => 'Purchase id is missing.'], 422);
        }

        $statusUrl = ($this->usesSandbox($settings) ? self::SANDBOX_STATUS_URL : self::LIVE_STATUS_URL)
            .$merchantId.'/purchaseId/'.$purchaseId;

        $response = Http::withHeaders([
            'Authorization' => 'Token '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get($statusUrl);

        $responseData = $response->json() ?? [];

        if (! $response->ok()) {
            return response()->json([
                'message' => 'Mintpay status request failed.',
                'data' => $responseData,
            ], 502);
        }

        if (($responseData['message'] ?? '') === "Order doesn't exists") {
            return response()->json($responseData, 404);
        }

        $status = (string) data_get($responseData, 'data.status', '');
        $paymentStatus = $this->mapStatus($status);

        $payment->forceFill([
            'payment_status' => $paymentStatus,
            'gateway_response' => $responseData,
        ]);

        if ($paymentStatus === 'paid' && ! $payment->payment_date) {
            $payment->payment_date = now();
        }

        $payment->save();

        if ($payment->order) {
            $payment->order->payment_status = $paymentStatus;

            if ($paymentStatus === 'paid') {
                $payment->order->status = 'processing';
            }

            $payment->order->save();
        }

        return response()->json([
            'message' => 'Mintpay status fetched.',
            'payment' => $payment->fresh('paymentMethod'),
            'gateway' => $responseData,
        ]);
    }

    private function mapStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'approved' => 'paid',
            'rejected' => 'failed',
            'failed' => 'failed',
            'cancelled' => 'failed',
            default => 'pending',
        };
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
