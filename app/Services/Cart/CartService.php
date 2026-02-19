<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;

class CartService
{
    public function resolveCart(?User $user, ?string $sessionId, bool $createIfMissing = true): ?Cart
    {
        $sessionId = $sessionId ? trim($sessionId) : null;

        if (! $user && blank($sessionId)) {
            abort(response()->json(['message' => 'Provide session_id for guest cart access.'], 422));
        }

        if ($user) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->first();
            if ($cart) {
                return $this->loadCartRelations($cart);
            }
        }

        if ($sessionId) {
            $cart = Cart::query()
                ->whereNull('user_id')
                ->where('session_id', $sessionId)
                ->first();
            if ($cart) {
                if ($user && $cart->user_id === null) {
                    $cart->forceFill([
                        'user_id' => $user->id,
                        'session_id' => null,
                    ])->save();
                }

                return $this->loadCartRelations($cart);
            }
        }

        if (! $createIfMissing) {
            return null;
        }

        $cart = Cart::create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $sessionId,
        ]);

        return $this->loadCartRelations($cart);
    }

    public function recalculateTotals(Cart $cart): Cart
    {
        $cart->loadMissing('items');

        $subtotal = $cart->items->sum('line_total');

        $cart->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => $subtotal,
        ])->save();

        return $this->refreshCart($cart);
    }

    public function transformCart(?Cart $cart): ?array
    {
        if (! $cart) {
            return null;
        }

        $cart = $this->loadCartRelations($cart);

        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'session_id' => $cart->session_id,
            'subtotal' => $cart->subtotal,
            'tax_total' => $cart->tax_total,
            'discount_total' => $cart->discount_total,
            'grand_total' => $cart->grand_total,
            'items' => $cart->items->map(
                fn (CartItem $item) => $this->transformCartItem($item)
            )->values(),
        ];
    }

    public function refreshCart(Cart $cart): Cart
    {
        $freshCart = $cart->fresh($this->cartRelations());

        return $freshCart ?? $this->loadCartRelations($cart);
    }

    public function loadCartRelations(Cart $cart): Cart
    {
        return $cart->loadMissing($this->cartRelations());
    }

    /**
     * @return list<string>
     */
    public function cartRelations(): array
    {
        return [
            'items.variant',
            'items.variant.product',
            'items.variant.product.primaryImage',
            'items.variant.images',
            'items.variant.colors',
            'items.variant.size',
        ];
    }

    private function transformCartItem(CartItem $item): array
    {
        $variant = $item->variant;
        $product = $variant?->product;
        $variantImage = $variant?->images->first();
        $primaryImage = $product?->primaryImage;
        $imagePath = $variantImage?->path ?? $primaryImage?->path;

        return [
            'id' => $item->id,
            'product_variant_id' => $item->product_variant_id,
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'variant_display_name' => $variant?->display_name,
            'variant_sku' => $variant?->sku,
            'size' => $variant?->size?->name,
            'color' => $variant?->color_names,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'line_total' => $item->line_total,
            'image_url' => $this->resolveImageUrl($imagePath),
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        return filled($path) ? url('storage/'.$path) : null;
    }
}
