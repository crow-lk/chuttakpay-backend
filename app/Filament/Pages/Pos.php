<?php

namespace App\Filament\Pages;

use App\Filament\Actions\ProcessSaleAction;
use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;

class Pos extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.pos';

    /**
     * Filter form state
     */
    public ?array $data = [
        'search' => null,
        'category_id' => null,
    ];

    /**
     * Cart stored in session
     */
    public array $cart = [];

    /**
     * Variant selection state for POS
     */
    public ?int $variantProductId = null;

    public array $variantSizes = [];

    public array $variantColors = [];

    public ?int $selectedSizeId = null;

    public ?int $selectedColorId = null;

    public ?int $selectedVariantId = null;

    public function mount(): void
    {
        $this->cart = session('cart', []);
    }

    /**
     * Search & category filter form
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('search')
                    ->inlineLabel()
                    ->placeholder('Search by product name')
                    ->type('search')
                    ->live(),

                Select::make('category_id')
                    ->label('Category')
                    ->inlineLabel()
                    ->placeholder('Select category')
                    ->options(
                        fn () => Category::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->live(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    /**
     * Products list
     */
    public function getProductsProperty(): Paginator
    {
        return Product::query()
            ->when(
                $this->data['search'] ?? null,
                fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%")
            )
            ->when(
                $this->data['category_id'] ?? null,
                fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId)
            )
            ->where('status', 'active')
            ->with([
                'category',
                'variants',
                'variants.stockLevels',
                'variants.colors',
                'stockLevels',
                'primaryImage',
            ])
            ->paginate(12);
    }

    /**
     * Cart items hydrated with product & variant data
     */
    public function getCartItemsProperty(): \Illuminate\Support\Collection
    {
        if (empty($this->cart)) {
            return collect();
        }

        $productIds = array_keys($this->cart);

        // Load all products with relations
        $products = Product::with(['variants', 'variants.stockLevels', 'variants.size', 'variants.colors', 'primaryImage', 'stockLevels'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($this->cart)
            ->filter(fn ($item) => $products->has($item['product_id']))
            ->map(function ($item) use ($products) {
                $product = $products->get($item['product_id']);

                // Total stock of all variants
                $item['stock'] = $product->stockLevels->sum('on_hand');

                $item['thumbnail_url'] = $product->primaryImage
                ? Storage::disk('public')->url($product->primaryImage->path)
                : null;

                // Include variant details - respect selected variant in the cart if present
                $selectedVariantId = $item['variant']['id'] ?? null;
                $variant = $selectedVariantId
                    ? $product->variants->firstWhere('id', $selectedVariantId)
                    : $product->variants->first();
                $item['variant'] = $variant ? [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->selling_price,
                    'stock' => $variant->stockLevels->sum('on_hand'),
                    'size' => $variant->size?->name,
                    'color' => $variant->color_names,
                ] : null;

                return $item;
            });
    }

    /**
     * Trigger pagination reset on search
     */
    public function search(): void
    {
        $this->resetPage();
    }

    /**
     * Clear filters
     */
    public function clearFilters(): void
    {
        $this->resetPage();

        $this->data = [
            'search' => null,
            'category_id' => null,
        ];
    }

    /**
     * Add product to cart
     */
    public function addToCart(int $productId): void
    {
        $product = Product::with(['variants', 'variants.size', 'variants.colors', 'stockLevels'])->find($productId);

        if (! $product) {
            return;
        }

        $stock = $product->stockLevels->sum('on_hand');
        $currentQty = $this->cart[$productId]['quantity'] ?? 0;

        if ($currentQty >= $stock) {
            Notification::make()
                ->title('Stock Unavailable')
                ->body("{$product->name} has reached the available stock limit.")
                ->warning()
                ->send();

            return;
        }
        // If product has multiple variants with size/color options, open selector
        $hasOptions = $product->variants->contains(fn ($v) => $v->size_id !== null) || $product->variants->contains(fn ($v) => $v->colors->isNotEmpty());

        if ($hasOptions && $product->variants->count() > 1) {
            $this->openVariantSelector($product);

            return;
        }

        // Fallback: quick add first variant (no options)
        $variant = $product->variants->first();
        $price = $variant?->selling_price ?? 0;

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        } else {
            $this->cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $price,
                'quantity' => 1,
                'variant' => $variant ? [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->selling_price,
                    'stock' => $variant->stockLevels->sum('on_hand'),
                    'size' => $variant->size?->name,
                    'color' => $variant->color_names,
                ] : null,
            ];
        }

        session(['cart' => $this->cart]);
    }

    /**
     * Prepare and open variant selector for a product
     */
    public function openVariantSelector(Product $product): void
    {
        $this->variantProductId = $product->id;

        $sizes = $product->variants
            ->filter(fn ($v) => $v->size_id !== null)
            ->mapWithKeys(fn ($v) => [$v->size_id => $v->size?->name])
            ->unique()
            ->toArray();

        $colors = $product->variants
            ->flatMap(fn ($v) => $v->colors)
            ->filter()
            ->unique('id')
            ->sortBy('sort_order')
            ->mapWithKeys(fn ($color) => [$color->id => $color->name])
            ->unique()
            ->toArray();

        $this->variantSizes = $sizes;
        $this->variantColors = $colors;

        // Reset selection
        $this->selectedSizeId = array_key_first($this->variantSizes) ?: null;
        $this->selectedColorId = array_key_first($this->variantColors) ?: null;

        $this->updateSelectedVariant();
    }

    public function closeVariantSelector(): void
    {
        $this->variantProductId = null;
        $this->variantSizes = [];
        $this->variantColors = [];
        $this->selectedSizeId = null;
        $this->selectedColorId = null;
        $this->selectedVariantId = null;
    }

    /**
     * Update selected variant based on current size/color selections
     */
    public function updateSelectedVariant(): void
    {
        if ($this->variantProductId === null) {
            $this->selectedVariantId = null;

            return;
        }

        $product = Product::with(['variants', 'variants.stockLevels', 'variants.colors'])->find($this->variantProductId);

        if (! $product) {
            $this->selectedVariantId = null;

            return;
        }

        // Normalize selected ids to integers when available
        $selectedSizeId = $this->selectedSizeId !== null ? (int) $this->selectedSizeId : null;
        $selectedColorId = $this->selectedColorId !== null ? (int) $this->selectedColorId : null;

        $variant = $product->variants->first(function ($v) use ($selectedSizeId, $selectedColorId) {
            $sizeMatch = $selectedSizeId ? $v->size_id === $selectedSizeId : true;
            $colorMatch = $selectedColorId ? $v->colors->contains('id', $selectedColorId) : true;

            return $sizeMatch && $colorMatch;
        });

        $this->selectedVariantId = $variant?->id;
    }

    /**
     * Confirm add to cart with selected size/color
     */
    public function confirmAddSelectedVariant(): void
    {
        if ($this->variantProductId === null) {
            return;
        }

        $product = Product::with(['variants', 'variants.size', 'variants.colors', 'variants.stockLevels'])->find($this->variantProductId);

        if (! $product) {
            return;
        }

        $variant = $product->variants->firstWhere('id', $this->selectedVariantId);

        if (! $variant) {
            Notification::make()
                ->title('Variant not available')
                ->body('Please select a valid size and color option.')
                ->warning()
                ->send();

            return;
        }

        $available = $variant->stockLevels->sum('on_hand');
        $currentQty = $this->cart[$product->id]['quantity'] ?? 0;

        if ($currentQty >= $available) {
            Notification::make()
                ->title('Stock Unavailable')
                ->body("{$product->name} ({$variant->size?->name} / {$variant->color_names}) has reached the available stock limit.")
                ->warning()
                ->send();

            return;
        }

        if (isset($this->cart[$product->id])) {
            $this->cart[$product->id]['quantity']++;
            // Update variant snapshot and price if changed
            $this->cart[$product->id]['price'] = $variant->selling_price;
            $this->cart[$product->id]['variant'] = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->selling_price,
                'stock' => $variant->stockLevels->sum('on_hand'),
                'size' => $variant->size?->name,
                'color' => $variant->color_names,
            ];
        } else {
            $this->cart[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $variant->selling_price,
                'quantity' => 1,
                'variant' => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->selling_price,
                    'stock' => $variant->stockLevels->sum('on_hand'),
                    'size' => $variant->size?->name,
                    'color' => $variant->color_names,
                ],
            ];
        }

        session(['cart' => $this->cart]);
        $this->closeVariantSelector();
    }

    /**
     * Remove item
     */
    public function removeFromCart(string $productId): void
    {
        unset($this->cart[$productId]);
        session(['cart' => $this->cart]);
    }

    /**
     * Increase quantity
     */
    public function incrementCartItem(string $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $product = Product::with(['stockLevels', 'variants', 'variants.stockLevels'])->find($productId);

        if (! $product) {
            return;
        }

        // Prefer variant-level stock if variant is selected
        $variantStock = null;
        if (($this->cart[$productId]['variant']['id'] ?? null) !== null) {
            $variant = $product->variants->firstWhere('id', $this->cart[$productId]['variant']['id']);
            $variantStock = $variant?->stockLevels->sum('on_hand');
        }

        $stock = $variantStock ?? $product->stockLevels->sum('on_hand');

        if ($this->cart[$productId]['quantity'] >= $stock) {
            $hasVariantLabels = (
                ($this->cart[$productId]['variant']['size'] ?? null) !== null
                || ($this->cart[$productId]['variant']['color'] ?? null) !== null
            );

            $variantText = $hasVariantLabels
                ? ' ('
                    .($this->cart[$productId]['variant']['size'] ?? '')
                    .' / '
                    .($this->cart[$productId]['variant']['color'] ?? '')
                    .').'
                : '.';

            Notification::make()
                ->title('Stock Limit Reached')
                ->body("You cannot add more of {$product->name}{$variantText}")
                ->warning()
                ->send();

            return;
        }

        $this->cart[$productId]['quantity']++;
        session(['cart' => $this->cart]);
    }

    /**
     * Decrease quantity
     */
    public function decrementCartItem(string $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId]['quantity'] > 1) {
            $this->cart[$productId]['quantity']--;
        } else {
            unset($this->cart[$productId]);
        }

        session(['cart' => $this->cart]);
    }

    /**
     * Clear cart
     */
    public function clearCart(): void
    {
        $this->cart = [];
        session(['cart' => $this->cart]);
    }

    /**
     * Cart total
     */
    public function getCartTotal(): float
    {
        return collect($this->cart)->sum(
            fn ($item) => $item['price'] * $item['quantity']
        );
    }

    /**
     * Process sale
     */
    public function createSaleAction(): Action
    {
        return ProcessSaleAction::make(
            $this->cart,
            fn () => $this->clearCart()
        );
    }
}
