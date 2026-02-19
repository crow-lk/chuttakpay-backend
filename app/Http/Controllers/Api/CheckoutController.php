<?php

namespace App\Http\Controllers\Api;

use App\Filament\Resources\Orders\OrderResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\InitiatePaymentRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Notifications\NotifyLkSmsService;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const PROMOTION_LIMIT = 100;

    private const FIRST_ORDER_DISCOUNT_PERCENT = 25;

    private const RETURNING_ORDER_DISCOUNT_PERCENT = 10;

    public function __construct(
        private readonly CartService $cartService,
        private readonly PaymentGatewayManager $paymentGatewayManager,
        private readonly OrderService $orderService,
        private readonly NotifyLkSmsService $smsService
    ) {}

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);

        $cart = $this->cartService->resolveCart(
            $user,
            $request->string('session_id')->toString(),
            false
        );

        if (! $cart) {
            return response()->json(['message' => 'No cart found to checkout.'], 404);
        }

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $cart = $this->cartService->recalculateTotals($cart);
        $cart = $this->applyFirst100OrdersPromotion(
            $cart,
            $user,
            (string) data_get($request->validated(), 'customer.email')
        );

        $paymentMethod = PaymentMethod::query()->findOrFail($request->integer('payment_method_id'));

        if (! $paymentMethod->active) {
            return response()->json(['message' => 'Selected payment method is inactive.'], 422);
        }

        $payment = Payment::create([
            'cart_id' => $cart->id,
            'amount_paid' => $cart->grand_total,
            'payment_method_id' => $paymentMethod->id,
            'gateway' => $paymentMethod->gateway,
            'payment_status' => 'pending',
        ]);

        $this->ensureReferenceNumber($payment);
        $payment->setRelation('paymentMethod', $paymentMethod);

        $context = [
            'customer' => $request->validated('customer'),
            'items' => $request->input('items') ?? ('Order '.$payment->reference_number),
            'cart' => $cart,
            'user_id' => $user?->id,
            'ip' => $request->ip(),
            'x_forwarded_for' => (string) $request->header('X-Forwarded-For', ''),
        ];

        if ($request->has('shipping')) {
            $context['shipping'] = $request->input('shipping');
        }

        foreach (['return_url', 'cancel_url', 'notify_url', 'success_url', 'fail_url'] as $urlKey) {
            if ($request->filled($urlKey)) {
                $context[$urlKey] = $request->string($urlKey)->toString();
            }
        }

        $checkoutData = $this->paymentGatewayManager
            ->forMethod($paymentMethod)
            ->prepareCheckout($payment, $context);

        $payment->forceFill(['gateway_payload' => $checkoutData])->save();

        return response()->json([
            'message' => 'Payment initialized.',
            'payment' => $payment->fresh('paymentMethod'),
            'checkout' => $checkoutData,
        ], 201);
    }

    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);

        $cart = $this->cartService->resolveCart(
            $user,
            $request->string('session_id')->toString(),
            false
        );

        if (! $cart) {
            return response()->json(['message' => 'No cart found to checkout.'], 404);
        }

        $cart->loadMissing('items');

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $cart = $this->cartService->recalculateTotals($cart);
        $cart = $this->applyFirst100OrdersPromotion(
            $cart,
            $user,
            (string) data_get($request->validated(), 'shipping.email')
        );
        $payment = $this->resolvePaymentForOrder($cart, $request);

        $order = $this->orderService->createFromCart($cart, $payment, [
            'currency' => $request->input('currency', 'LKR'),
            'shipping_total' => $request->float('shipping_total', 0),
            'notes' => $request->input('notes'),
            'shipping_address' => $request->input('shipping'),
            'billing_address' => $request->input('billing'),
            'customer_email' => data_get($request->validated(), 'shipping.email'),
            'customer_phone' => data_get($request->validated(), 'shipping.phone'),
        ]);

        $this->sendOrderPlacedSms($order);

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $order->load('items'),
        ], 201);
    }

    private function resolvePaymentForOrder(Cart $cart, PlaceOrderRequest $request): Payment
    {
        if ($request->filled('payment_id')) {
            $payment = Payment::query()
                ->with('paymentMethod')
                ->findOrFail($request->integer('payment_id'));

            if ($payment->cart_id !== $cart->id) {
                throw ValidationException::withMessages([
                    'payment_id' => 'Payment does not match the current cart.',
                ]);
            }

            if ($request->filled('payment_method_id') && $payment->payment_method_id !== $request->integer('payment_method_id')) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'Payment method mismatch.',
                ]);
            }

            return $payment;
        }

        $paymentMethod = PaymentMethod::query()->findOrFail($request->integer('payment_method_id'));

        if (! $paymentMethod->active) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Selected payment method is inactive.',
            ]);
        }

        if ($this->requiresGatewayCompletion($paymentMethod)) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Please initiate this payment method before placing the order.',
            ]);
        }

        $payment = Payment::create([
            'cart_id' => $cart->id,
            'amount_paid' => $cart->grand_total,
            'payment_method_id' => $paymentMethod->id,
            'gateway' => $paymentMethod->gateway,
            'payment_status' => 'pending',
        ]);

        $this->ensureReferenceNumber($payment);
        $payment->setRelation('paymentMethod', $paymentMethod);

        if ($paymentMethod->gateway === 'manual_bank') {
            $receiptPath = $this->storePaymentReceipt($request);
            if ($receiptPath) {
                $payment->forceFill(['receipt_path' => $receiptPath])->save();
            }
        }

        return $payment->fresh('paymentMethod');
    }

    private function storePaymentReceipt(PlaceOrderRequest $request): ?string
    {
        if (! $request->hasFile('payment_receipt')) {
            return null;
        }

        return $request->file('payment_receipt')->store('payment-receipts', 'public');
    }

    private function ensureReferenceNumber(Payment $payment): void
    {
        if (! $payment->reference_number) {
            $payment->forceFill(['reference_number' => 'PAY-'.$payment->id])->save();
        }
    }

    private function requiresGatewayCompletion(PaymentMethod $paymentMethod): bool
    {
        return in_array($paymentMethod->gateway, ['payhere', 'mintpay'], true);
    }

    private function sendOrderPlacedSms(Order $order): void
    {
        if (! $this->smsService->hasCredentials()) {
            return;
        }

        $this->sendAdminOrderPlacedSms($order);

        $phone = (string) $order->customer_phone;

        if (! filled($phone)) {
            return;
        }

        $recipient = $this->smsService->normalizeRecipient($phone);

        if ($recipient === '' || ! $this->smsService->isValidRecipient($recipient)) {
            return;
        }

        $message = sprintf(
            "Thank you for your order!\nOrder No: %s\nTotal: LKR %.2f",
            $order->order_number,
            $order->grand_total
        );

        try {
            $this->smsService->send(
                to: $recipient,
                message: $message,
                contact: [
                    'first_name' => data_get($order, 'shipping_address.first_name'),
                    'last_name' => data_get($order, 'shipping_address.last_name'),
                    'email' => $order->customer_email,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Order SMS failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendAdminOrderPlacedSms(Order $order): void
    {
        $adminPhone = (string) config('services.notifylk.admin_phone', '0703363363');
        $recipient = $this->smsService->normalizeRecipient($adminPhone);

        if ($recipient === '' || ! $this->smsService->isValidRecipient($recipient)) {
            return;
        }

        $message = $this->buildAdminOrderMessage($order);

        if ($message === '') {
            return;
        }

        try {
            $this->smsService->send(
                to: $recipient,
                message: $message
            );
        } catch (\Throwable $exception) {
            Log::error('Admin order SMS failed', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function buildAdminOrderMessage(Order $order): string
    {
        $orderNumber = (string) ($order->order_number ?? $order->id);
        $currency = (string) ($order->currency ?? 'LKR');
        $grandTotal = number_format((float) ($order->grand_total ?? 0), 2, '.', '');
        $customerName = trim((string) ($order->customer_name ?? $order->user?->name ?? ''));
        $customerPhone = trim((string) $order->customer_phone);
        $orderUrl = OrderResource::getUrl('view', ['record' => $order]);

        $lines = [
            "New order {$orderNumber}",
            "Total: {$currency} {$grandTotal}",
        ];

        if ($customerName !== '') {
            $lines[] = "Customer: {$customerName}";
        }

        if ($customerPhone !== '') {
            $lines[] = "Phone: {$customerPhone}";
        }

        $lines[] = $orderUrl;

        return Str::limit(implode("\n", $lines), 320, '');
    }

    private function applyFirst100OrdersPromotion(Cart $cart, ?User $user, string $email): Cart
    {
        if (! $this->isFirst100OrdersPromotionEnabled()) {
            return $cart;
        }

        if ($this->isPromotionLimitReached()) {
            return $cart;
        }

        $discountPercent = $this->isReturningCustomer($user, $email)
            ? self::RETURNING_ORDER_DISCOUNT_PERCENT
            : self::FIRST_ORDER_DISCOUNT_PERCENT;

        $discountTotal = round($cart->subtotal * ($discountPercent / 100), 2);

        $cart->forceFill([
            'discount_total' => $discountTotal,
            'grand_total' => max($cart->subtotal - $discountTotal, 0),
        ])->save();

        return $this->cartService->refreshCart($cart);
    }

    private function isFirst100OrdersPromotionEnabled(): bool
    {
        return filter_var(
            Setting::getValue('promotions.first_100_orders_enabled', '0'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function isPromotionLimitReached(): bool
    {
        return Order::query()->count() >= self::PROMOTION_LIMIT;
    }

    private function isReturningCustomer(?User $user, string $email): bool
    {
        if ($user?->id) {
            return Order::query()
                ->where('user_id', $user->id)
                ->exists();
        }

        $email = strtolower(trim($email));

        if ($email === '') {
            return false;
        }

        return Order::query()
            ->where('customer_email', $email)
            ->exists();
    }
}
