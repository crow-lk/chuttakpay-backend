<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['store', 'warehouse']);
        $name = $type === 'store'
            ? sprintf('%s Store', fake()->city())
            : sprintf('%s Warehouse', ucfirst(fake()->word()));

        return [
            'name' => $name,
            'type' => $type,
        ];
    }
}
