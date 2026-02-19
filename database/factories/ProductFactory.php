<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'sku_prefix' => Str::upper(Str::random(4)),
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'collection_id' => fake()->boolean(60) ? Collection::factory() : null,
            'season' => fake()->optional()->word(),
            'description' => fake()->paragraphs(2, true),
            'care_instructions' => fake()->optional()->sentences(2, true),
            'material_composition' => fake()->optional()->words(3, true),
            'hs_code' => fake()->optional()->lexify('HS#####'),
            'default_tax_id' => fake()->boolean(70) ? Tax::factory() : null,
            'status' => fake()->randomElement(['draft', 'active', 'discontinued']),
        ];
    }
}
