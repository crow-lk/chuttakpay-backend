<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'variant_id' => ProductVariant::factory(),
            'location_id' => Location::factory(),
            'quantity' => fake()->numberBetween(-20, 50),
            'reason' => fake()->randomElement(ProductVariant::STOCK_REASONS),
            'reference_type' => fake()->optional()->randomElement(['purchase_order', 'sale_order', 'transfer']),
            'reference_id' => fake()->optional()->numberBetween(1, 1000),
            'notes' => fake()->optional()->sentence(),
            'created_by' => fake()->boolean(40) ? User::factory() : null,
        ];
    }
}
