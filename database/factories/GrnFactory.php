<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grn>
 */
class GrnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => \App\Models\Supplier::factory(),
            'purchase_order_id' => fake()->optional()->randomElement(\App\Models\PurchaseOrder::pluck('id')->toArray()),
            'received_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => fake()->randomElement(['pending', 'received', 'verified']),
            'remarks' => fake()->optional()->paragraph(),
            'location_id' => \App\Models\Location::factory(),
        ];
    }
}
