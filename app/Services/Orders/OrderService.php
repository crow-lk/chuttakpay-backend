<?php

namespace App\Services\Orders;

use App\Jobs\SendOrderPlacedSms;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * @param  array{
     *     currency?: string,
     *     shipping_total?: float|int,
     *     billing_address?: array<string, mixed>|null,
     *     shipping_address?: array<string, mixed>|null,
     *     customer_email?: string|null,
     *     customer_phone?: string|null,
     *     notes?: string|null,
     *     location_id?: int|null
     * }  $data
     */
    public function createFromCart(Cart $cart, ?Payment $payment, array $data): Order
    {
        $cart->loadMissing([
            'items.variant.product',
            'items.variant.colors',
            'items.variant.size',
        ]);

        return DB::transaction(function () use ($cart, $payment, $data) {
            $orderNumber = $this->generateOrderNumber();

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $cart->user_id,
                'payment_method_id' => $payment?->payment_method_id,
                'payment_id' => $payment?->id,
                'status' => 'pending',
                'payment_status' => $payment?->payment_status ?? 'pending',
                'fulfillment_status' => 'unfulfilled',
                'currency' => $data['currency'] ?? 'LKR',
                'subtotal' => $cart->subtotal,
                'tax_total' => $cart->tax_total,
                'discount_total' => $cart->discount_total,
                'shipping_total' => $data['shipping_total'] ?? 0,
                'grand_total' => $cart->grand_total + ($data['shipping_total'] ?? 0),
                'billing_address' => $data['billing_address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $locationId = $this->resolveStockLocationId($data['location_id'] ?? null);

            foreach ($cart->items as $item) {
                $variant = $item->variant;
                $product = $variant?->product;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product?->name ?? 'Product',
                    'variant_name' => $variant?->display_name,
                    'sku' => $variant?->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'meta' => [
                        'size' => $variant?->size?->name,
                        'color' => $variant?->color_names,
                    ],
                ]);

                if ($variant) {
                    $quantity = (int) $item->quantity;

                    $variant->adjustStock(
                        $locationId,
                        -$quantity,
                        'sale',
                        [
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                            'notes' => "Sold {$quantity} units",
                            'created_by' => $cart->user_id,
                        ]
                    );
                }
            }

            $cart->items()->delete();
            $cart->forceFill([
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'grand_total' => 0,
            ])->save();

            if ($payment) {
                $payment->order()->associate($order);
                $payment->save();

                $order->payment_status = $payment->payment_status;
                $order->save();
            }

            SendOrderPlacedSms::dispatch($order)->afterCommit();

            return $order->load('items');
        });
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
    }

    private function resolveStockLocationId(?int $locationId): int
    {
        return $locationId ?? 1;
    }
}
