<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayHereWebhookController extends Controller
{
    public function __construct(private readonly PaymentGatewayManager $paymentGatewayManager) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;

        if (! $orderId) {
            return response()->json(['message' => 'order_id is required.'], 422);
        }

        $payment = Payment::query()
            ->where('reference_number', $orderId)
            ->with('paymentMethod')
            ->first();

        if (! $payment || ! $payment->paymentMethod) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        $notification = $this->paymentGatewayManager
            ->forMethod($payment->paymentMethod)
            ->verifyNotification($payload, $payment->paymentMethod);

        $payment->gateway_response = $payload;

        if ($notification->verified) {
            $payment->payment_status = $notification->status;

            if ($notification->isSuccessful() && ! $payment->payment_date) {
                $payment->payment_date = now();
            }
        }

        $payment->save();

        if ($notification->verified && $payment->order) {
            $payment->order->payment_status = $notification->status;
            if ($notification->isSuccessful()) {
                $payment->order->status = 'processing';
            }

            $payment->order->save();
        }

        return response()->json([
            'message' => 'Notification processed.',
            'verified' => $notification->verified,
            'status' => $notification->status,
        ]);
    }
}
