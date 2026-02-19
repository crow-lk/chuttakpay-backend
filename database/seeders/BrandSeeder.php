<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Aaliyaa'],
            ['name' => 'Generic'],
        ];

        foreach ($brands as $brand) {
            Brand::query()->updateOrCreate(
                ['name' => $brand['name']],
                []
            );
        }
    }
}
