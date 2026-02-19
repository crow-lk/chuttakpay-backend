<x-filament-panels::page>
    <div class="page-root">
        <div class="page-grid">
            <!-- Main Content -->
            <div class="main-content">
                <!-- Search Form Section -->
                <section class="section">
                    <form wire:submit.prevent="search" class="form">
                        <div class="form-field">{{ $this->form }}</div>
                    </form>
                </section>

                <!-- Products Grid Section -->
                <section class="section">
                    <div class="products-grid">
                        @forelse ($this->products as $product)
                            @php
                                $quantity = $product->stock ?? $product->stockLevels->sum('on_hand');
                                $thumbnail = $product->thumbnail_url
                                ?? ($product->primaryImage
                                    ? Storage::disk('public')->url($product->primaryImage->path)
                                    : 'https://placehold.co/600x600?text=' . urlencode($product->name));
                                $price = $product->variants->first()?->selling_price ?? 0;
                            @endphp

                               <div class="product-card {{ $quantity > 0 ? 'clickable' : 'out-of-stock' }}"
                                   @if($quantity > 0) wire:click="addToCart({{ $product->id }})" @endif>

                                @if($quantity <= 0)
                                    <div class="overlay">Out of Stock</div>
                                @endif

                                <div class="stock-badge {{ $quantity > 10 ? 'in-stock-high' : ($quantity > 0 ? 'in-stock-low' : 'out-of-stock-badge') }}">
                                    {{ $quantity }} in stock
                                </div>

                                <div class="product-image">
                                    <img src="{{ $thumbnail }}" alt="{{ $product->name }}">
                                </div>

                                <div class="product-details">
                                    <h3>{{ $product->name }}</h3>
                                    <p>{{ $product->category?->name }}</p>
                                    <div class="price">{{ number_format($price, 2) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="no-products">
                                <p>No products found. Try changing your search or filter criteria.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="pagination">
                        {{ $this->products->links('livewire.filament-pagination') }}
                    </div>
                </section>
            </div>

            <!-- Cart Sidebar -->
            <div class="cart-sidebar">
                <section class="section sticky">
                    <h2>Cart</h2>

                    <div class="cart-items">
                        @forelse ($this->cartItems as $item)
                            <div class="cart-item">
                                <div class="item-info">
                                    <img src="{{ $item['thumbnail_url'] ?? 'https://placehold.co/100x100?text=' . urlencode($item['name']) }}" alt="{{ $item['name'] }}">
                                    <div>
                                        <p>{{ $item['name'] }}</p>
                                        <p>{{ number_format($item['price'], 2) }}</p>
                                        @if(isset($item['variant']))
                                            <p class="variant-info">
                                                {{ $item['variant']['size'] ?? '' }} {{ $item['variant']['color'] ?? '' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="item-controls">
                                    <button wire:click="decrementCartItem('{{ $item['product_id'] }}')">-</button>
                                    <span>{{ $item['quantity'] }}</span>
                                    <button wire:click="incrementCartItem('{{ $item['product_id'] }}')" @if($item['quantity'] >= $item['stock']) disabled @endif>+</button>
                                </div>
                                <div class="item-total">{{ number_format($item['price'] * $item['quantity'], 2) }}</div>
                                <button wire:click="removeFromCart('{{ $item['product_id'] }}')">🗑️</button>
                            </div>
                        @empty
                            <p>Your cart is empty.</p>
                        @endforelse
                    </div>

                    @if ($this->cartItems->isNotEmpty())
                        <div class="cart-total">
                            <span>Total:</span>
                            <span>{{ number_format($this->getCartTotal(), 2) }}</span>
                        </div>
                        <div class="cart-actions">
                            {{ $this->createSaleAction }}
                            <button wire:click="clearCart" class="btn btn-danger">Clear Cart</button>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

    <!-- Variant Select Modal -->
    @if($this->variantProductId)
        @php
            $currentProduct = \App\Models\Product::find($this->variantProductId);
        @endphp
        <div class="variant-modal">
            <div class="variant-dialog">
                <div class="variant-header">
                    <h3>Select Options</h3>
                    <button class="close" wire:click="closeVariantSelector">✕</button>
                </div>
                <div class="variant-body">
                    @if($currentProduct)
                        <div class="variant-product">
                            <strong>{{ $currentProduct->name }}</strong>
                            <span class="category">{{ $currentProduct->category?->name }}</span>
                        </div>
                    @endif
                    <div class="variant-form">
                        @if(!empty($this->variantSizes))
                            <label>Size</label>
                            <select class="select" wire:model="selectedSizeId" wire:change="updateSelectedVariant">
                                @foreach($this->variantSizes as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if(!empty($this->variantColors))
                            <label>Color</label>
                            <select class="select" wire:model="selectedColorId" wire:change="updateSelectedVariant">
                                @foreach($this->variantColors as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        @endif

                        <div class="variant-summary">
                                @if($this->selectedVariantId)
                                    @php
                                        $variant = \App\Models\ProductVariant::with(['size','colors','stockLevels'])->find($this->selectedVariantId);
                                    @endphp
                                    @if($variant)
                                        <div>Price: <strong>{{ number_format($variant->selling_price, 2) }}</strong></div>
                                        <div>Available: <strong>{{ $variant->stockLevels->sum('on_hand') }}</strong></div>
                                        <div class="chips">
                                            @if($variant->size?->name)
                                                <span class="chip">{{ $variant->size->name }}</span>
                                            @endif
                                            @if($variant->color_names)
                                                <span class="chip">{{ $variant->color_names }}</span>
                                            @endif
                                        </div>
                                    @endif
                            @else
                                <div class="warning">No matching variant for selected options.</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="variant-footer">
                    <button class="btn" wire:click="confirmAddSelectedVariant" @if(!$this->selectedVariantId) disabled @endif>Add to cart</button>
                    <button class="btn btn-secondary" wire:click="closeVariantSelector">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Normal CSS -->
    <style>
        .page-root { padding: 1rem; }
        .page-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        .main-content, .cart-sidebar { display: flex; flex-direction: column; }
        .section { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .product-card { position: relative; border: 1px solid #ccc; border-radius: 8px; overflow: hidden; cursor: default; transition: transform 0.3s, box-shadow 0.3s; }
        .product-card.clickable:hover { transform: scale(1.05); box-shadow: 0 4px 8px rgba(0,0,0,0.1); cursor: pointer; }
        .product-card.out-of-stock { cursor: not-allowed; }
        .product-card .overlay { position: absolute; inset: 0; background: rgba(128,128,128,0.7); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }
        .product-card .product-image img { width: 100%; aspect-ratio: 1/1; object-fit: cover; }
        .product-details h3 { font-size: 0.9rem; margin: 0.3rem 0; }
        .product-details p { font-size: 0.8rem; color: #666; margin: 0; }
        .product-details .price { font-weight: bold; color: #1a73e8; }
        .stock-badge { position: absolute; top: 0.5rem; right: 0.5rem; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.7rem; }
        .in-stock-high { background: #d4edda; color: #155724; }
        .in-stock-low { background: #fff3cd; color: #856404; }
        .out-of-stock-badge { background: #e2e3e5; color: #6c757d; }

        .cart-sidebar .sticky { position: sticky; top: 1.25rem; }
        .cart-item { display: flex; align-items: center; justify-content: space-between; background: #f9f9f9; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.5rem; }
        .item-info { display: flex; gap: 0.5rem; flex-grow: 1; }
        .item-info img { width: 48px; height: 48px; object-fit: cover; border-radius: 4px; }
        .item-controls { display: flex; align-items: center; gap: 0.3rem; }
        .item-controls button { width: 24px; height: 24px; border: none; background: #eee; border-radius: 4px; cursor: pointer; }
        .item-total { width: 60px; text-align: right; font-weight: bold; }
        .cart-total { display: flex; justify-content: space-between; font-weight: bold; margin-top: 1rem; }
        .cart-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .btn { padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; border: none; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; }
        .variant-info { font-size: 0.75rem; color: #555; }

        /* Variant modal styles */
        .variant-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 50; }
        .variant-dialog { width: 500px; max-width: 90vw; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .variant-header { display: flex; align-items: center; justify-content: space-between; padding: 0.8rem 1rem; border-bottom: 1px solid #eee; }
        .variant-header .close { border: none; background: transparent; font-size: 1.2rem; cursor: pointer; }
        .variant-body { padding: 1rem; }
        .variant-product { display: flex; gap: 0.5rem; align-items: baseline; margin-bottom: 0.5rem; }
        .variant-product .category { color: #777; font-size: 0.85rem; }
        .variant-form label { display: block; font-size: 0.85rem; color: #444; margin-top: 0.5rem; }
        .variant-form .select { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #ddd; border-radius: 6px; margin-top: 0.3rem; }
        .variant-summary { margin-top: 0.8rem; }
        .variant-summary .chips { display: flex; gap: 0.4rem; margin-top: 0.4rem; flex-wrap: wrap; }
        .chip { background: #f1f3f5; color: #333; border-radius: 999px; padding: 0.2rem 0.6rem; font-size: 0.75rem; }
        .variant-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 0.8rem 1rem; border-top: 1px solid #eee; }
        .btn.btn-secondary { background: #e9ecef; color: #333; }
    </style>
</x-filament-panels::page>
