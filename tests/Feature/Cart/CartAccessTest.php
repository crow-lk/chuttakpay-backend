<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_cart_requests_use_the_user_cart_even_with_guest_session_id(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->create([
            'selling_price' => 1900,
            'status' => 'active',
        ]);

        $cart = Cart::create([
            'session_id' => 'guest-cart',
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

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/cart?session_id=guest-cart');

        $response->assertOk()
            ->assertJsonPath('id', $cart->id)
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.product_variant_id', $variant->id)
            ->assertJsonPath('items.0.quantity', 2);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }
}
