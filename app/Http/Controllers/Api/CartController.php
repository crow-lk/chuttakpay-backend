<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\MergeCartRequest;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function show(Request $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);

        $cart = $this->cartService->resolveCart(
            $user,
            $request->string('session_id')->toString(),
            true
        );

        return response()->json($this->cartService->transformCart($cart));
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);

        $cart = $this->cartService->resolveCart(
            $user,
            $request->string('session_id')->toString(),
            true
        );

        $variant = ProductVariant::with('product')->findOrFail($request->integer('product_variant_id'));

        if ($variant->product?->inquiry_only) {
            return response()->json([
                'message' => 'This product is inquiry only and cannot be added to the cart.',
            ], 422);
        }

        if ($variant->selling_price === null) {
            return response()->json([
                'message' => 'This product has no price set and cannot be added to the cart.',
            ], 422);
        }

        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);

        $item->quantity = $item->exists
            ? $item->quantity + $request->integer('quantity')
            : $request->integer('quantity');

        $item->unit_price = $variant->selling_price;
        $item->line_total = $item->quantity * $variant->selling_price;
        $item->save();

        $cart = $this->cartService->recalculateTotals($cart);

        return response()->json([
            'message' => 'Item added to cart',
            'cart' => $this->cartService->transformCart($cart),
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        $cart = $this->cartService->resolveCart($user, $request->string('session_id')->toString(), false);
        $this->guardCartItem($cart, $cartItem);

        // Handle variant switching
        if ($request->filled('product_variant_id')) {
            $newVariant = ProductVariant::with('product')->findOrFail($request->integer('product_variant_id'));

            // Merge if cart already has this variant
            $existing = $cart->items()
                ->where('product_variant_id', $newVariant->id)
                ->where('id', '!=', $cartItem->id)
                ->first();

            if ($existing) {
                $existing->quantity += $cartItem->quantity;
                $existing->line_total = $existing->quantity * $existing->unit_price;
                $existing->save();
                $cartItem->delete();
            } else {
                $cartItem->product_variant_id = $newVariant->id;
                $cartItem->unit_price = $newVariant->selling_price;
            }
        }

        // Update quantity if provided
        if ($request->filled('quantity')) {
            $cartItem->quantity = $request->integer('quantity');
        }

        // Always recalc line total
        $cartItem->line_total = $cartItem->quantity * $cartItem->unit_price;
        $cartItem->save();

        $cart = $this->cartService->recalculateTotals($cart);

        return response()->json([
            'message' => 'Cart item updated',
            'cart' => $this->cartService->transformCart($cart),
        ]);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        $cart = $this->cartService->resolveCart($user, $request->string('session_id')->toString(), false);
        $this->guardCartItem($cart, $cartItem);

        $cartItem->delete();
        $cart = $this->cartService->recalculateTotals($cart);

        return response()->json([
            'message' => 'Cart item removed',
            'cart' => $this->cartService->transformCart($cart),
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        $cart = $this->cartService->resolveCart($user, $request->string('session_id')->toString(), false);

        if ($cart !== null) {
            $cart->items()->delete();
            $cart = $this->cartService->recalculateTotals($cart);
        }

        return response()->json([
            'message' => 'Cart cleared',
            'cart' => $cart ? $this->cartService->transformCart($cart) : null,
        ]);
    }

    public function merge(MergeCartRequest $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        $sessionId = $request->string('session_id')->toString();

        $guestCart = Cart::query()->whereNull('user_id')->where('session_id', $sessionId)->with('items')->first();
        if (!$guestCart) return response()->json(['message' => 'No guest cart to merge.']);

        $userCart = Cart::query()->where('user_id', $user->id)->with('items')->first();

        if (!$userCart) {
            $guestCart->forceFill(['user_id' => $user->id, 'session_id' => null])->save();
            $cart = $guestCart;
        } else {
            foreach ($guestCart->items as $item) {
                $existing = $userCart->items->firstWhere('product_variant_id', $item->product_variant_id);
                if ($existing) {
                    $existing->quantity += $item->quantity;
                    $existing->line_total = $existing->quantity * $existing->unit_price;
                    $existing->save();
                } else {
                    $item->cart_id = $userCart->id;
                    $item->save();
                }
            }
            $guestCart->items()->delete();
            $guestCart->delete();
            $cart = $userCart->fresh('items');
        }

        $cart = $this->cartService->recalculateTotals($cart);

        return response()->json([
            'message' => 'Cart merged',
            'cart' => $this->cartService->transformCart($cart),
        ]);
    }

    private function guardCartItem(?Cart $cart, CartItem $cartItem): void
    {
        if (!$cart || $cartItem->cart_id !== $cart->id) {
            abort(404);
        }
    }
}
