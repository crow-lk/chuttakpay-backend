<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET ALL PRODUCTS
     */
    public function index()
    {
        $products = Product::with([
            'primaryImage',
            'images',
            'collection',
            'variants',
            'variants.images',
            'variants.size',
            'variants.stockLevels',
            'variants.colors',
        ])
            ->where(function ($query) {
                $query
                    ->whereNull('collection_id')
                    ->orWhereHas('collection', function ($q) {
                        $q->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now());
                    });
            })
            ->get();

        return $products->map(function (Product $product) {

            $hidePrice = $product->inquiry_only && ! $product->show_price_inquiry_mode;

            $variants = $product->variants->map(function ($variant) use ($hidePrice) {

                $availableQty = $variant->stockLevels->sum(
                    fn ($stock) => $stock->on_hand - $stock->reserved
                );
                $primaryColor = $variant->colors->first();

                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'size_id' => $variant->size_id,
                    'size_name' => $variant->size?->name,
                    'color' => $primaryColor
                        ? [
                            'id' => $primaryColor->id,
                            'name' => $primaryColor->name,
                            'hex' => $primaryColor->hex,
                        ]
                        : null,
                    'colors' => $variant->colors
                        ->map(fn ($color) => [
                            'id' => $color->id,
                            'name' => $color->name,
                            'hex' => $color->hex,
                        ])
                        ->values(),
                    'selling_price' => $hidePrice ? null : $variant->selling_price,
                    'quantity' => $availableQty,
                    'status' => $variant->status,
                ];
            });

            $totalQuantity = $variants->sum('quantity');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku_prefix' => $product->sku_prefix,
                'brand_id' => $product->brand_id,
                'category_id' => $product->category_id,
                'collection_id' => $product->collection_id,
                'collection_name' => $product->collection?->name,
                'season' => $product->season,
                'description' => $product->description,
                'care_instructions' => $product->care_instructions,
                'material_composition' => $product->material_composition,
                'hs_code' => $product->hs_code,
                'default_tax_id' => $product->default_tax_id,
                'status' => $product->status,
                'quantity' => $totalQuantity,
                'inquiry_only' => (bool) $product->inquiry_only,
                'show_price_inquiry_mode' => (bool) $product->show_price_inquiry_mode,
                'variants' => $variants,
                'images' => $product->images
                    ->map(fn ($img) => url('storage/'.$img->path))
                    ->values(),
                'highlights' => array_values(array_filter([
                    $product->season ? ($product->season.' ready') : null,
                    $product->collection_id ? ('Collection '.$product->collection_id) : null,
                ])),
            ];
        });
    }

    /**
     * SHOW SINGLE PRODUCT
     */
    public function show($id)
    {
        $product = Product::with([
            'primaryImage',
            'images',
            'collection',
            'variants',
            'variants.images',
            'variants.size',
            'variants.stockLevels',
            'variants.colors',
        ])
            ->find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Hide expired collection dynamically
        if (
            $product->collection &&
            now()->notBetween(
                $product->collection->start_date,
                $product->collection->end_date
            )
        ) {
            $product->collection_id = null;
            $product->setRelation('collection', null);
        }

        return response()->json($this->transformProduct($product));
    }

    /**
     * EDIT PRODUCT
     */
    public function edit($id)
    {
        return $this->show($id);
    }

    /**
     * UPDATE PRODUCT
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->update($request->all());

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * DELETE PRODUCT
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    /**
     * TRANSFORM SINGLE PRODUCT
     */
    private function transformProduct(Product $product): array
    {
        $hidePrice = $product->inquiry_only && ! $product->show_price_inquiry_mode;

        $variants = $product->variants->map(function ($variant) use ($hidePrice) {

            $availableQty = $variant->stockLevels->sum(
                fn ($stock) => $stock->on_hand - $stock->reserved
            );
            $primaryColor = $variant->colors->first();

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'size_id' => $variant->size_id,
                'size_name' => $variant->size?->name,
                'color' => $primaryColor
                    ? [
                        'id' => $primaryColor->id,
                        'name' => $primaryColor->name,
                        'hex' => $primaryColor->hex,
                    ]
                    : null,
                'colors' => $variant->colors
                    ->map(fn ($color) => [
                        'id' => $color->id,
                        'name' => $color->name,
                        'hex' => $color->hex,
                    ])
                    ->values(),
                'selling_price' => $hidePrice ? null : $variant->selling_price,
                'quantity' => $availableQty,
                'status' => $variant->status,
            ];
        });

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku_prefix' => $product->sku_prefix,
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'collection_id' => $product->collection_id,
            'season' => $product->season,
            'description' => $product->description,
            'care_instructions' => $product->care_instructions,
            'material_composition' => $product->material_composition,
            'hs_code' => $product->hs_code,
            'default_tax_id' => $product->default_tax_id,
            'status' => $product->status,
            'quantity' => $variants->sum('quantity'),
            'inquiry_only' => (bool) $product->inquiry_only,
            'show_price_inquiry_mode' => (bool) $product->show_price_inquiry_mode,
            'variants' => $variants,
            'images' => $product->images
                ->map(fn ($img) => url('storage/'.$img->path))
                ->values(),
            'highlights' => array_values(array_filter([
                $product->season ? ($product->season.' ready') : null,
                $product->collection_id ? ('Collection '.$product->collection_id) : null,
            ])),
        ];
    }
}
