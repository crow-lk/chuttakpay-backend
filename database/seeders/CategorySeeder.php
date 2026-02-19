<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $women = Category::query()->updateOrCreate(
            ['slug' => 'women'],
            [
                'name' => 'Women',
                'parent_id' => null,
            ]
        );

        Category::query()->updateOrCreate(
            ['slug' => 'dresses'],
            [
                'name' => 'Dresses',
                'parent_id' => $women->getKey(),
            ]
        );
    }
}
