<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tax;
use Illuminate\Database\Seeder;

class DashboardSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        if (StockMovement::query()->where('reference_type', 'demo-metrics')->exists()) {
            return;
        }

        $brand = Brand::query()->first();
        $category = Category::query()->first();
        $location = Location::query()->first();
        $tax = Tax::query()->first();

        if (! $brand || ! $category || ! $location) {
            return;
        }

        $product = Product::query()->firstOrCreate(
            ['slug' => 'demo-summer-dress'],
            [
                'name' => 'Demo Summer Dress',
                'sku_prefix' => 'DEMO',
                'brand_id' => $brand->getKey(),
                'category_id' => $category->getKey(),
                'collection_id' => null,
                'season' => 'SS'.now()->format('y'),
                'description' => 'Demo product used to populate dashboard metrics.',
                'care_instructions' => 'Machine wash cold, demo only.',
                'material_composition' => '100% Cotton',
                'hs_code' => '6104.42',
                'default_tax_id' => $tax?->getKey(),
                'status' => 'active',
            ]
        );

        $variant = $product->variants()->firstOrCreate(
            ['sku' => 'DEMO-001'],
            [
                'barcode' => '910000000001',
                'size_id' => null,
                'cost_price' => 2500,
                'mrp' => 4500,
                'selling_price' => 3990,
                'reorder_point' => 10,
                'reorder_qty' => 20,
                'weight_grams' => 350,
                'status' => 'active',
            ]
        );

        $variant->adjustStock(
            locationId: $location->getKey(),
            quantity: 120,
            reason: 'purchase',
            meta: [
                'reference_type' => 'demo-metrics',
                'reference_id' => 1,
                'notes' => 'Sample purchase to generate dashboard metrics.',
            ]
        );

        $variant->adjustStock(
            locationId: $location->getKey(),
            quantity: -45,
            reason: 'sale',
            meta: [
                'reference_type' => 'demo-metrics',
                'reference_id' => 2,
                'notes' => 'Sample sale to generate dashboard metrics.',
            ]
        );
    }
}
