<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class SchemaAndModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_expected_tables_and_columns(): void
    {
        $expected = [
            'users' => ['id', 'name', 'email', 'password', 'role', 'created_at', 'updated_at'],
            'restaurants' => ['id', 'name', 'logo', 'description', 'address', 'phone', 'currency', 'is_active', 'created_at', 'updated_at'],
            'categories' => ['id', 'name', 'description', 'is_active', 'sort_order', 'created_at', 'updated_at', 'deleted_at'],
            'menu_items' => ['id', 'category_id', 'name', 'description', 'price', 'image_path', 'is_available', 'is_featured', 'sort_order', 'created_at', 'updated_at', 'deleted_at'],
            'restaurant_tables' => ['id', 'table_number', 'qr_token', 'is_active', 'created_at', 'updated_at'],
            'settings' => ['id', 'key', 'value', 'created_at', 'updated_at'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
            $this->assertTrue(Schema::hasColumns($table, $columns), "Missing columns on {$table}");
        }
    }

    public function test_category_has_many_menu_items_and_item_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $items = MenuItem::factory()->count(2)->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->menuItems);
        $this->assertTrue($items->first()->category->is($category));
    }

    public function test_price_is_decimal_string_without_float_rounding(): void
    {
        $item = MenuItem::factory()->create(['price' => '0.10']);
        $item->refresh();

        $this->assertSame('0.10', $item->price);

        $item->update(['price' => '123456789.99']);
        $this->assertSame('123456789.99', $item->fresh()->price);
    }

    public function test_boolean_and_integer_casts(): void
    {
        $item = MenuItem::factory()->create(['is_available' => 1, 'is_featured' => 0, 'sort_order' => '7']);
        $item = $item->fresh();

        $this->assertTrue($item->is_available);
        $this->assertFalse($item->is_featured);
        $this->assertSame(7, $item->sort_order);
    }

    public function test_foreign_key_rejects_unknown_category(): void
    {
        $this->expectException(QueryException::class);

        MenuItem::factory()->create(['category_id' => 9999]);
    }

    public function test_category_with_items_cannot_be_force_deleted(): void
    {
        $category = Category::factory()->create();
        MenuItem::factory()->create(['category_id' => $category->id]);

        $this->expectException(QueryException::class);

        $category->forceDelete();
    }

    public function test_soft_deleted_category_and_item_are_hidden_but_restorable(): void
    {
        $category = Category::factory()->create();
        $item = MenuItem::factory()->create(['category_id' => $category->id]);

        $item->delete();
        $category->delete();

        $this->assertNull(Category::find($category->id));
        $this->assertNull(MenuItem::find($item->id));
        $this->assertNotNull(Category::withTrashed()->find($category->id));
    }

    public function test_table_number_and_setting_key_are_unique(): void
    {
        RestaurantTable::factory()->create(['table_number' => 'A1']);
        Setting::create(['key' => 'theme', 'value' => 'light']);

        try {
            RestaurantTable::factory()->create(['table_number' => 'A1']);
            $this->fail('Duplicate table_number was accepted.');
        } catch (QueryException) {
            $this->assertSame(1, RestaurantTable::where('table_number', 'A1')->count());
        }

        $this->expectException(QueryException::class);
        Setting::create(['key' => 'theme', 'value' => 'dark']);
    }

    public function test_qr_token_is_generated_unique_and_not_mass_assignable(): void
    {
        $a = RestaurantTable::create(['table_number' => 'T1', 'qr_token' => 'attacker-chosen']);
        $b = RestaurantTable::create(['table_number' => 'T2']);

        $this->assertNotSame('attacker-chosen', $a->qr_token);
        $this->assertSame(RestaurantTable::TOKEN_LENGTH, strlen($a->qr_token));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{48}$/', $a->qr_token);
        $this->assertNotSame($a->qr_token, $b->qr_token);
    }

    public function test_only_one_restaurant_record_is_allowed(): void
    {
        Restaurant::factory()->create();

        $this->expectException(LogicException::class);
        Restaurant::factory()->create();
    }

    public function test_setting_helpers_store_and_update_values(): void
    {
        Setting::put('tagline', 'Hello');
        Setting::put('tagline', 'Hi');

        $this->assertSame('Hi', Setting::get('tagline'));
        $this->assertSame('fallback', Setting::get('missing', 'fallback'));
        $this->assertSame(1, Setting::where('key', 'tagline')->count());
    }

    public function test_user_role_is_not_mass_assignable(): void
    {
        $user = new User(['name' => 'X', 'email' => 'x@example.com', 'password' => 'p', 'role' => 'superadmin']);

        $this->assertNotSame('superadmin', $user->role);
    }
}
