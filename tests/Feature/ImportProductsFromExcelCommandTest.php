<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportProductsFromExcelCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_items_from_excel_with_defaults(): void
    {
        $brand = Brand::factory()->create(['name' => 'Default Brand']);
        $category = Category::factory()->create(['name' => 'General']);
        $this->artisan('products:import-excel', [
            '--file' => 'public/products_list/DOC-20260123-WA0034.xlsx',
            '--brand' => $brand->name,
            '--category' => $category->name,
            '--limit' => 2,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('product_variants', 2);

        $this->assertDatabaseHas('products', [
            'name' => 'Milk tea color Qu Qixing accessories combination rubber band (4)',
            'hs_code' => '9.61511E7',
            'brand_id' => $brand->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $variant = ProductVariant::query()->where('sku', '4012201984')->firstOrFail();

        $this->assertSame('0.15', $variant->selling_price);
        $this->assertSame('0.15', $variant->cost_price);

        $product = Product::query()->where('name', 'Milk tea color Qu Qixing accessories combination rubber band (4)')->firstOrFail();

        $this->assertSame('active', $product->status);
    }
}
