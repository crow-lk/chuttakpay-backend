<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            ['name' => 'Black', 'hex' => '#000000', 'sort_order' => 10],
            ['name' => 'White', 'hex' => '#FFFFFF', 'sort_order' => 20],
            ['name' => 'Red', 'hex' => '#FF0000', 'sort_order' => 30],
        ];

        foreach ($colors as $color) {
            Color::query()->updateOrCreate(
                ['name' => $color['name']],
                [
                    'hex' => $color['hex'],
                    'sort_order' => $color['sort_order'],
                ]
            );
        }
    }
}
