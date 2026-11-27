<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class RestaurantTableSeeder extends Seeder
{
    /**
     * Tokens are generated once on creation and never changed by re-seeding.
     */
    public function run(): void
    {
        foreach (range(1, 5) as $number) {
            RestaurantTable::firstOrCreate(
                ['table_number' => sprintf('Table %02d', $number)],
                ['is_active' => true],
            );
        }
    }
}
