<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['name' => 'Colombo Store', 'type' => 'store'],
            ['name' => 'Main Warehouse', 'type' => 'warehouse'],
        ];

        foreach ($locations as $location) {
            Location::query()->updateOrCreate(
                ['name' => $location['name']],
                ['type' => $location['type']]
            );
        }
    }
}
