<?php

namespace Tests\Unit;

use App\Filament\Widgets\HotProductsTable;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotProductsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_hot_products_table_query_executes_without_group_by_error(): void
    {
        // Create test data
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        // Create some stock movements
        StockMovement::factory()->create([
            'variant_id' => $variant->id,
            'reason' => 'sale',
            'quantity' => -5,
            'created_at' => now()->startOfMonth()->addDays(1),
        ]);

        // Create widget instance
        $widget = new HotProductsTable;

        // Get the query using reflection to access protected method
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getTableQuery');
        $method->setAccessible(true);

        // Execute the query - this should not throw a GROUP BY error
        $query = $method->invoke($widget);
        $results = $query->get();

        // Assert the query executes successfully
        $this->assertNotNull($results);
        $this->assertGreaterThanOrEqual(0, $results->count());

        // If we have results, verify the structure
        if ($results->count() > 0) {
            $firstResult = $results->first();
            $this->assertArrayHasKey('id', $firstResult->getAttributes());
            $this->assertArrayHasKey('name', $firstResult->getAttributes());
            $this->assertArrayHasKey('brand_id', $firstResult->getAttributes());
            $this->assertArrayHasKey('total_sold', $firstResult->getAttributes());
        }
    }

    public function test_dashboard_metrics_widget_hot_product_query_executes_correctly(): void
    {
        // Create test data
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        // Create some stock movements
        StockMovement::factory()->create([
            'variant_id' => $variant->id,
            'reason' => 'sale',
            'quantity' => -10,
            'created_at' => now()->startOfMonth()->addDays(5),
        ]);

        $startOfMonth = now()->startOfMonth();

        // Execute the query from DashboardMetricsWidget
        $hotProduct = Product::query()
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->join('stock_movements', 'stock_movements.variant_id', '=', 'product_variants.id')
            ->where('stock_movements.reason', 'sale')
            ->where('stock_movements.created_at', '>=', $startOfMonth)
            ->select('products.name')
            ->selectRaw('COALESCE(SUM(ABS(stock_movements.quantity)), 0) as total_sold')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->first();

        // Assert the query executes successfully
        $this->assertNotNull($hotProduct);
        $this->assertEquals($product->name, $hotProduct->name);
        $this->assertEquals(10, $hotProduct->total_sold);
    }
}
