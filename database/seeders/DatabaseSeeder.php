<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            SizeSeeder::class,
            ColorSeeder::class,
            TaxSeeder::class,
            LocationSeeder::class,
            PaymentMethodSeeder::class,
            DashboardSampleDataSeeder::class,
        ]);

        if (User::query()->where('email', 'test@example.com')->doesntExist()) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

            $user->assignRole('admin');
        }
    }
}
