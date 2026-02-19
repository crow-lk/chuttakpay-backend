<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
 */
class TaxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rate = fake()->randomFloat(2, 1, 25);

        return [
            'name' => sprintf('%s %s%%', strtoupper(fake()->word()), number_format($rate, 2)),
            'rate' => $rate,
            'is_inclusive' => fake()->boolean(),
        ];
    }
}
