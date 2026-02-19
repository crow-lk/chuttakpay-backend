<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $startDate = fake()->optional()->dateTimeBetween('-1 year', 'now');
        $endDate = null;

        if ($startDate !== null && fake()->boolean()) {
            $endDate = fake()->dateTimeBetween($startDate, (clone $startDate)->modify('+6 months'));
        }

        return [
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
}
