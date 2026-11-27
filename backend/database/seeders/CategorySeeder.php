<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Existing categories (including soft-deleted ones) are never recreated or overwritten.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Rice', 'description' => 'Rice dishes and fried rice.'],
            ['name' => 'Noodles', 'description' => 'Stir-fried and soup noodles.'],
            ['name' => 'Chicken', 'description' => 'Chicken specialties.'],
            ['name' => 'Drinks', 'description' => 'Hot and cold beverages.'],
            ['name' => 'Desserts', 'description' => 'Sweet treats.'],
        ];

        foreach ($categories as $index => $category) {
            Category::withTrashed()->firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description'], 'is_active' => true, 'sort_order' => $index + 1],
            );
        }
    }
}
