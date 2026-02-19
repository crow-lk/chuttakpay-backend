<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Color>
 */
class ColorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->safeColorName()),
            'hex' => sprintf('#%06X', fake()->numberBetween(0, 0xFFFFFF)),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
