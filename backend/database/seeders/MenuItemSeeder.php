<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Prices are decimal strings. Existing items are never overwritten.
     */
    public function run(): void
    {
        $items = [
            'Rice' => [
                ['Chicken Fried Rice', 'Wok-fried rice with chicken, egg and vegetables.', '8.50', true],
                ['Seafood Fried Rice', 'Fried rice with prawns and squid.', '10.50', false],
                ['Steamed Rice', 'Plain jasmine rice.', '1.50', false],
            ],
            'Noodles' => [
                ['Pad Thai', 'Rice noodles, tamarind sauce, peanuts and lime.', '9.00', true],
                ['Beef Noodle Soup', 'Slow-simmered broth with sliced beef.', '9.50', false],
                ['Vegetable Chow Mein', 'Stir-fried egg noodles with vegetables.', '7.50', false],
            ],
            'Chicken' => [
                ['Grilled Chicken', 'Herb-marinated grilled chicken thigh.', '11.00', true],
                ['Crispy Fried Chicken', 'Four pieces, house spice mix.', '10.00', false],
                ['Chicken Curry', 'Coconut curry with potatoes.', '9.75', false],
            ],
            'Drinks' => [
                ['Iced Lemon Tea', 'Fresh brewed tea with lemon.', '2.50', false],
                ['Fresh Orange Juice', 'Squeezed to order.', '3.50', false],
                ['Coffee', 'Hot americano.', '2.75', false],
            ],
            'Desserts' => [
                ['Mango Sticky Rice', 'Sweet coconut sticky rice with ripe mango.', '5.50', true],
                ['Chocolate Cake', 'Rich dark chocolate slice.', '4.75', false],
                ['Ice Cream Scoop', 'Vanilla, chocolate or strawberry.', '3.00', false],
            ],
        ];

        foreach ($items as $categoryName => $rows) {
            $category = Category::withTrashed()->where('name', $categoryName)->first();

            if (! $category) {
                continue;
            }

            foreach ($rows as $index => [$name, $description, $price, $featured]) {
                MenuItem::withTrashed()->firstOrCreate(
                    ['category_id' => $category->id, 'name' => $name],
                    [
                        'description' => $description,
                        'price' => $price,
                        'is_available' => true,
                        'is_featured' => $featured,
                        'sort_order' => $index + 1,
                    ],
                );
            }
        }
    }
}
