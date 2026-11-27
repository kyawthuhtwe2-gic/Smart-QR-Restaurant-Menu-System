<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        if (Restaurant::query()->exists()) {
            return;
        }

        Restaurant::create([
            'name' => 'Smart QR Restaurant',
            'description' => 'Fresh food, served your way. Scan, browse and enjoy.',
            'address' => '123 Main Street',
            'phone' => '+1 555 0100',
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }
}
