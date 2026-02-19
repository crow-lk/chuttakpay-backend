<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GrnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create suppliers
        $suppliers = \App\Models\Supplier::factory()->count(5)->create();

        // Create purchase orders
        $purchaseOrders = \App\Models\PurchaseOrder::factory()
            ->count(10)
            ->recycle($suppliers)
            ->create();

        // Create GRNs with items
        $locations = \App\Models\Location::all();
        $products = \App\Models\Product::with('variants')->get();

        foreach ($suppliers as $supplier) {
            $grns = \App\Models\Grn::factory()
                ->count(fake()->numberBetween(1, 3))
                ->create([
                    'supplier_id' => $supplier->id,
                    'purchase_order_id' => $purchaseOrders->where('supplier_id', $supplier->id)->random()?->id,
                    'location_id' => $locations->random()->id,
                ]);

            foreach ($grns as $grn) {
                $selectedProducts = $products->random(min(fake()->numberBetween(1, 2), $products->count()));

                foreach ($selectedProducts as $product) {
                    \App\Models\GrnItem::factory()->create([
                        'grn_id' => $grn->id,
                        'product_id' => $product->id,
                        'variant_id' => $product->variants->first()?->id,
                    ]);
                }
            }
        }
    }
}
