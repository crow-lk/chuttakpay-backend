<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tax::query()->updateOrCreate(
            ['name' => 'VAT 15%'],
            [
                'rate' => 15.00,
                'is_inclusive' => false,
            ]
        );
    }
}
