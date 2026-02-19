<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockLevel>
 */
class StockLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'variant_id' => ProductVariant::factory(),
            'on_hand' => fake()->numberBetween(0, 100),
            'reserved' => fake()->numberBetween(0, 20),
        ];
    }
}
