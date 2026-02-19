<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 1500, 5000);
        $selling = $cost + fake()->randomFloat(2, 500, 1500);

        return [
            'product_id' => Product::factory(),
            'sku' => null,
            'barcode' => null,
            'size_id' => fake()->boolean(80) ? Size::factory() : null,
            'cost_price' => $cost,
            'mrp' => fake()->optional(0.6)->randomFloat(2, $selling, $selling + 2000),
            'selling_price' => $selling,
            'reorder_point' => fake()->optional()->numberBetween(5, 20),
            'reorder_qty' => fake()->optional()->numberBetween(10, 30),
            'weight_grams' => fake()->optional()->numberBetween(100, 1000),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ProductVariant $variant): void {
            if ($variant->colors()->exists()) {
                return;
            }

            if (! fake()->boolean(80)) {
                return;
            }

            $count = fake()->boolean(20) ? 2 : 1;
            $colors = Color::factory()->count($count)->create();

            $variant->colors()->sync($colors->modelKeys());
        });
    }
}
