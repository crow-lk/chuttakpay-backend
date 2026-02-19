<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'po_number' => 'PO-'.str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'supplier_id' => \App\Models\Supplier::factory(),
            'order_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'expected_delivery_date' => fake()->dateTimeBetween('now', '+30 days'),
            'status' => fake()->randomElement(['draft', 'sent', 'confirmed', 'partially_received', 'received', 'cancelled']),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
