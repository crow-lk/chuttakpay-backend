<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InitiatePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_initiate_manual_payment(): void
    {
        $variant = ProductVariant::factory()->create([
            'selling_price' => 1500,
            'status' => 'active',
        ]);

        $cart = Cart::create([
            'session_id' => 'session-123',
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->selling_price,
            'line_total' => $variant->selling_price,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'Bank Transfer',
            'code' => 'BANK',
            'type' => 'offline',
            'gateway' => 'manual_bank',
            'description' => 'Pay via bank transfer.',
            'instructions' => 'Transfer funds to the provided bank account.',
            'sort_order' => 1,
            'settings' => [
                'account_name' => 'Test Store',
            ],
            'active' => true,
        ]);

        $payload = [
            'payment_method_id' => $paymentMethod->id,
            'session_id' => 'session-123',
            'customer' => [
                'first_name' => 'Aaliya',
                'last_name' => 'Customer',
                'email' => 'customer@example.com',
                'phone' => '0771234567',
                'address' => '123, Flower Road',
                'city' => 'Colombo',
                'country' => 'Sri Lanka',
            ],
        ];

        $response = $this->postJson('/api/checkout/payments', $payload);

        $response->assertCreated()
            ->assertJsonPath('checkout.type', 'manual')
            ->assertJsonPath('payment.payment_method_id', $paymentMethod->id);

        $this->assertDatabaseHas('payments', [
            'cart_id' => $cart->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_can_place_order_from_cart_and_payment(): void
    {
        $variant = ProductVariant::factory()->create([
            'selling_price' => 2500,
            'status' => 'active',
        ]);

        $cart = Cart::create([
            'session_id' => 'session-order',
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => $variant->selling_price,
            'line_total' => $variant->selling_price * 2,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'PayHere',
            'code' => 'PAYHERE',
            'type' => 'online',
            'gateway' => 'payhere',
            'description' => 'PayHere payment',
            'instructions' => null,
            'sort_order' => 1,
            'settings' => [],
            'active' => true,
        ]);

        $payment = Payment::create([
            'cart_id' => $cart->id,
            'amount_paid' => $variant->selling_price * 2,
            'payment_method_id' => $paymentMethod->id,
            'gateway' => 'manual_bank',
            'payment_status' => 'pending',
            'reference_number' => 'PAY-TEST-1',
        ]);

        $payload = [
            'payment_id' => $payment->id,
            'session_id' => 'session-order',
            'shipping' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line1' => '221B Baker Street',
                'city' => 'London',
                'country' => 'UK',
                'postal_code' => 'NW16XE',
                'email' => 'john@example.com',
                'phone' => '0771234567',
            ],
        ];

        $response = $this->postJson('/api/checkout/orders', $payload);

        $response->assertCreated()
            ->assertJsonPath('order.payment_id', $payment->id)
            ->assertJsonPath('order.items.0.quantity', 2);

        $order = Order::query()->first();

        $this->assertNotNull($order);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
        ]);
    }

    public function test_can_place_cod_order_without_existing_payment(): void
    {
        $variant = ProductVariant::factory()->create([
            'selling_price' => 3000,
            'status' => 'active',
        ]);

        $cart = Cart::create([
            'session_id' => 'session-cod',
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->selling_price,
            'line_total' => $variant->selling_price,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'Cash On Delivery',
            'code' => 'COD',
            'type' => 'offline',
            'gateway' => 'cod',
            'description' => 'Cash payment at delivery.',
            'instructions' => null,
            'sort_order' => 1,
            'settings' => [],
            'active' => true,
        ]);

        $payload = [
            'payment_method_id' => $paymentMethod->id,
            'session_id' => 'session-cod',
            'shipping' => $this->shippingPayload(),
        ];

        $response = $this->postJson('/api/checkout/orders', $payload);

        $response->assertCreated()
            ->assertJsonPath('order.items.0.quantity', 1);

        $order = Order::query()->first();
        $payment = Payment::query()->where('payment_method_id', $paymentMethod->id)->first();

        $this->assertNotNull($order);
        $this->assertNotNull($payment);
        $this->assertEquals($order->id, $payment->order_id);
    }

    public function test_online_transfer_requires_receipt(): void
    {
        $variant = ProductVariant::factory()->create([
            'selling_price' => 1800,
            'status' => 'active',
        ]);

        $cart = Cart::create([
            'session_id' => 'session-transfer',
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->selling_price,
            'line_total' => $variant->selling_price,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'Online Transfer',
            'code' => 'BANK',
            'type' => 'offline',
            'gateway' => 'manual_bank',
            'description' => null,
            'instructions' => null,
            'sort_order' => 1,
            'settings' => [],
            'active' => true,
        ]);

        $payload = [
            'payment_method_id' => $paymentMethod->id,
            'session_id' => 'session-transfer',
            'shipping' => $this->shippingPayload(),
        ];

        $response = $this->postJson('/api/checkout/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('payment_receipt');
    }

    public function test_can_place_online_transfer_with_receipt(): void
    {
        Storage::fake('public');

        $variant = ProductVariant::factory()->create([
            'selling_price' => 1800,
            'status' => 'active',
        ]);

        $cart = Cart::create([
            'session_id' => 'session-transfer-file',
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => $variant->selling_price,
            'line_total' => $variant->selling_price * 2,
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'Online Transfer',
            'code' => 'BANK',
            'type' => 'offline',
            'gateway' => 'manual_bank',
            'description' => null,
            'instructions' => null,
            'sort_order' => 1,
            'settings' => [],
            'active' => true,
        ]);

        $payload = [
            'payment_method_id' => $paymentMethod->id,
            'session_id' => 'session-transfer-file',
            'shipping' => $this->shippingPayload(),
            'payment_receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ];

        $response = $this->post('/api/checkout/orders', $payload, [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated();

        $payment = Payment::query()->first();

        $this->assertNotNull($payment?->receipt_path);
        Storage::disk('public')->assertExists($payment->receipt_path);
    }

    private function shippingPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line1' => '221B Baker Street',
            'city' => 'London',
            'country' => 'UK',
            'postal_code' => 'NW16XE',
            'email' => 'john@example.com',
            'phone' => '0771234567',
        ], $overrides);
    }
}
