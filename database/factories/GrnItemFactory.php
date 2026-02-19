<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GrnItem>
 */
class GrnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderedQty = fake()->numberBetween(1, 100);
        $receivedQty = fake()->numberBetween(1, $orderedQty);
        $unitCost = fake()->randomFloat(2, 10, 500);

        return [
            'grn_id' => \App\Models\Grn::factory(),
            'product_id' => \App\Models\Product::factory(),
            'variant_id' => function (array $attributes) {
                return \App\Models\ProductVariant::where('product_id', $attributes['product_id'])->first()?->id;
            },
            'ordered_qty' => $orderedQty,
            'received_qty' => $receivedQty,
            'unit_cost' => $unitCost,
            'total_cost' => $receivedQty * $unitCost,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
