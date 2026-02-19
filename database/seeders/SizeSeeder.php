<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = [
            ['name' => 'XS', 'sort_order' => 10],
            ['name' => 'S', 'sort_order' => 20],
            ['name' => 'M', 'sort_order' => 30],
            ['name' => 'L', 'sort_order' => 40],
            ['name' => 'XL', 'sort_order' => 50],
        ];

        foreach ($sizes as $size) {
            Size::query()->updateOrCreate(
                ['name' => $size['name']],
                ['sort_order' => $size['sort_order']]
            );
        }
    }
}
