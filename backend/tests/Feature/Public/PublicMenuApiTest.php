<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicMenuApiTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['name' => 'Golden Spoon', 'currency' => 'USD']);
        $this->table = RestaurantTable::factory()->create(['table_number' => 'Table 01']);
    }

    private function menuUrl(?string $token = null, string $suffix = ''): string
    {
        return '/api/v1/public/menu/'.($token ?? $this->table->qr_token).$suffix;
    }

    private function assertInvalidQr($response): void
    {
        $response->assertStatus(404)
            ->assertExactJson(['success' => false, 'message' => 'Invalid or inactive QR code', 'errors' => []]);
    }

    // ------------------------------------------------------------ valid access

    public function test_valid_token_returns_menu_without_login(): void
    {
        $rice = Category::factory()->create(['name' => 'Rice', 'sort_order' => 1]);
        MenuItem::factory()->create(['category_id' => $rice->id, 'name' => 'Fried Rice', 'price' => '8.50']);

        $this->getJson($this->menuUrl())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.restaurant.name', 'Golden Spoon')
            ->assertJsonPath('data.restaurant.currency', 'USD')
            ->assertJsonPath('data.table.table_number', 'Table 01')
            ->assertJsonPath('data.categories.0.name', 'Rice')
            ->assertJsonPath('data.categories.0.items.0.name', 'Fried Rice')
            ->assertJsonPath('data.categories.0.items.0.price', '8.50');
    }

    public function test_categories_and_items_endpoints_work_without_login(): void
    {
        $category = Category::factory()->create();
        MenuItem::factory()->count(2)->create(['category_id' => $category->id]);

        $this->getJson($this->menuUrl(null, '/categories'))->assertOk()->assertJsonCount(1, 'data.categories');
        $this->getJson($this->menuUrl(null, '/items'))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
    }

    public function test_responses_are_not_cacheable(): void
    {
        $response = $this->getJson($this->menuUrl())->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    // ------------------------------------------------------------ token validation

    public function test_unknown_malformed_and_hostile_tokens_get_identical_404(): void
    {
        foreach (['unknown', str_repeat('a', 48), "1' OR '1'='1", '1', str_repeat('x', 500), '%00'] as $token) {
            foreach (['', '/categories', '/items'] as $suffix) {
                $this->assertInvalidQr($this->getJson('/api/v1/public/menu/'.rawurlencode($token).$suffix));
            }
        }
    }

    public function test_sequential_database_ids_do_not_work_as_tokens(): void
    {
        $this->assertInvalidQr($this->getJson('/api/v1/public/menu/'.$this->table->id));
    }

    public function test_old_regenerated_token_returns_404_and_new_works(): void
    {
        $old = $this->table->qr_token;
        $this->table->regenerateToken();

        $this->assertInvalidQr($this->getJson($this->menuUrl($old)));
        $this->assertInvalidQr($this->getJson($this->menuUrl($old, '/categories')));
        $this->assertInvalidQr($this->getJson($this->menuUrl($old, '/items')));
        $this->getJson($this->menuUrl($this->table->qr_token))->assertOk();
    }

    public function test_inactive_table_returns_404_and_reactivation_restores_access(): void
    {
        $this->table->update(['is_active' => false]);

        $this->assertInvalidQr($this->getJson($this->menuUrl()));
        $this->assertInvalidQr($this->getJson($this->menuUrl(null, '/items')));

        $this->table->update(['is_active' => true]);
        $this->getJson($this->menuUrl())->assertOk();
    }

    public function test_deleted_table_returns_404(): void
    {
        $token = $this->table->qr_token;
        $this->table->delete();

        $this->assertInvalidQr($this->getJson($this->menuUrl($token)));
    }

    public function test_inactive_restaurant_returns_404_on_every_endpoint(): void
    {
        $this->restaurant->update(['is_active' => false]);

        $this->assertInvalidQr($this->getJson($this->menuUrl()));
        $this->assertInvalidQr($this->getJson($this->menuUrl(null, '/categories')));
        $this->assertInvalidQr($this->getJson($this->menuUrl(null, '/items')));
    }

    public function test_missing_restaurant_returns_404(): void
    {
        $this->restaurant->delete();

        $this->assertInvalidQr($this->getJson($this->menuUrl()));
    }

    // ------------------------------------------------------------ visibility rules

    public function test_unavailable_items_are_not_displayed(): void
    {
        $category = Category::factory()->create();
        MenuItem::factory()->create(['category_id' => $category->id, 'name' => 'Visible']);
        MenuItem::factory()->unavailable()->create(['category_id' => $category->id, 'name' => 'Sold Out Dish']);

        $json = $this->getJson($this->menuUrl())->assertOk();

        $this->assertSame(['Visible'], collect($json->json('data.categories.0.items'))->pluck('name')->all());
        $this->assertStringNotContainsString('Sold Out Dish', $json->getContent());
        $this->assertStringNotContainsString('Sold Out Dish', $this->getJson($this->menuUrl(null, '/items'))->getContent());
    }

    public function test_inactive_categories_and_their_items_are_not_displayed(): void
    {
        $inactive = Category::factory()->inactive()->create(['name' => 'Hidden Category']);
        MenuItem::factory()->create(['category_id' => $inactive->id, 'name' => 'Hidden Item']);
        $active = Category::factory()->create();
        MenuItem::factory()->create(['category_id' => $active->id]);

        $menu = $this->getJson($this->menuUrl())->assertOk();
        $items = $this->getJson($this->menuUrl(null, '/items'))->assertOk();

        $this->assertStringNotContainsString('Hidden', $menu->getContent());
        $this->assertStringNotContainsString('Hidden', $items->getContent());
        $this->assertStringNotContainsString('Hidden', $this->getJson($this->menuUrl(null, '/categories'))->getContent());
        $this->assertSame(1, $items->json('meta.total'));
    }

    public function test_items_of_inactive_category_cannot_be_reached_by_category_filter(): void
    {
        $inactive = Category::factory()->inactive()->create();
        MenuItem::factory()->create(['category_id' => $inactive->id]);

        $this->getJson($this->menuUrl(null, "/items?category_id={$inactive->id}"))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_soft_deleted_items_and_categories_are_not_displayed(): void
    {
        $category = Category::factory()->create(['name' => 'Gone Category']);
        $item = MenuItem::factory()->create(['category_id' => $category->id, 'name' => 'Gone Item']);
        $item->delete();

        $this->assertStringNotContainsString('Gone', $this->getJson($this->menuUrl())->getContent());

        $category->delete();
        $this->assertStringNotContainsString('Gone', $this->getJson($this->menuUrl())->getContent());
    }

    public function test_empty_categories_are_omitted(): void
    {
        Category::factory()->create(['name' => 'Empty']);

        $this->getJson($this->menuUrl())->assertOk()->assertJsonCount(0, 'data.categories');
    }

    public function test_categories_and_items_are_in_sort_order(): void
    {
        $second = Category::factory()->create(['name' => 'Second', 'sort_order' => 2]);
        $first = Category::factory()->create(['name' => 'First', 'sort_order' => 1]);
        MenuItem::factory()->create(['category_id' => $first->id, 'name' => 'B', 'sort_order' => 2]);
        MenuItem::factory()->create(['category_id' => $first->id, 'name' => 'A', 'sort_order' => 1]);
        MenuItem::factory()->create(['category_id' => $second->id]);

        $json = $this->getJson($this->menuUrl())->assertOk();

        $this->assertSame(['First', 'Second'], collect($json->json('data.categories'))->pluck('name')->all());
        $this->assertSame(['A', 'B'], collect($json->json('data.categories.0.items'))->pluck('name')->all());
    }

    public function test_featured_flag_is_returned_and_filterable(): void
    {
        $category = Category::factory()->create();
        MenuItem::factory()->featured()->create(['category_id' => $category->id, 'name' => 'Star']);
        MenuItem::factory()->create(['category_id' => $category->id, 'name' => 'Plain']);

        $this->getJson($this->menuUrl(null, '/items?is_featured=1'))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Star')->assertJsonPath('data.0.is_featured', true);
    }

    // ------------------------------------------------------------ dynamic updates

    public function test_updated_price_and_name_appear_without_new_qr(): void
    {
        $category = Category::factory()->create();
        $item = MenuItem::factory()->create(['category_id' => $category->id, 'name' => 'Pad Thai', 'price' => '9.00']);
        $url = $this->menuUrl();

        $this->getJson($url)->assertJsonPath('data.categories.0.items.0.price', '9.00');

        $item->update(['price' => '10.25', 'name' => 'Pad Thai Deluxe']);

        $this->getJson($url)
            ->assertJsonPath('data.categories.0.items.0.price', '10.25')
            ->assertJsonPath('data.categories.0.items.0.name', 'Pad Thai Deluxe');
        $this->assertSame($url, $this->menuUrl());
    }

    public function test_item_becoming_unavailable_disappears_and_returns_when_available_again(): void
    {
        $category = Category::factory()->create();
        $item = MenuItem::factory()->create(['category_id' => $category->id]);

        $item->update(['is_available' => false]);
        $this->getJson($this->menuUrl(null, '/items'))->assertJsonCount(0, 'data');

        $item->update(['is_available' => true]);
        $this->getJson($this->menuUrl(null, '/items'))->assertJsonCount(1, 'data');
    }

    public function test_restaurant_changes_appear_immediately(): void
    {
        $this->restaurant->update(['name' => 'Renamed Bistro', 'currency' => 'EUR']);

        $this->getJson($this->menuUrl())->assertJsonPath('data.restaurant.name', 'Renamed Bistro')->assertJsonPath('data.restaurant.currency', 'EUR');
    }

    // ------------------------------------------------------------ search & pagination

    public function test_search_matches_name_and_description_and_escapes_wildcards(): void
    {
        $category = Category::factory()->create();
        MenuItem::factory()->create(['category_id' => $category->id, 'name' => 'Chicken Curry', 'description' => 'Mild']);
        MenuItem::factory()->create(['category_id' => $category->id, 'name' => 'Soup', 'description' => 'Contains lemongrass']);

        $this->getJson($this->menuUrl(null, '/items?search=curry'))->assertJsonCount(1, 'data');
        $this->getJson($this->menuUrl(null, '/items?search=lemongrass'))->assertJsonCount(1, 'data');
        $this->getJson($this->menuUrl(null, '/items?search=%25'))->assertJsonCount(0, 'data');
    }

    public function test_items_pagination_and_limits(): void
    {
        $category = Category::factory()->create();
        MenuItem::factory()->count(12)->create(['category_id' => $category->id]);

        $this->getJson($this->menuUrl(null, '/items?per_page=5&page=3'))->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 12)->assertJsonPath('meta.last_page', 3);
        $this->getJson($this->menuUrl(null, '/items?per_page=1000'))->assertStatus(422);
    }

    // ------------------------------------------------------------ data exposure

    public function test_public_responses_expose_no_admin_or_token_data(): void
    {
        $category = Category::factory()->create();
        MenuItem::factory()->featured()->create(['category_id' => $category->id, 'image_path' => 'menu-items/x.png']);
        $admin = User::factory()->create(['email' => 'secret-admin@example.com']);

        $bodies = [
            $this->getJson($this->menuUrl())->getContent(),
            $this->getJson($this->menuUrl(null, '/categories'))->getContent(),
            $this->getJson($this->menuUrl(null, '/items'))->getContent(),
        ];

        $forbidden = [
            $this->table->qr_token, 'qr_token', 'image_path', 'is_available', 'deleted_at', 'sort_order',
            'created_at', 'updated_at', 'is_active', 'password', 'role', $admin->email, 'token',
        ];

        foreach ($bodies as $body) {
            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString($needle, $body, "Public response leaks [{$needle}]");
            }
        }
    }

    public function test_authenticated_admin_gets_the_same_public_view(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = Category::factory()->create();
        MenuItem::factory()->unavailable()->create(['category_id' => $category->id]);

        $this->getJson($this->menuUrl(null, '/items'))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_public_endpoints_are_read_only(): void
    {
        $this->postJson($this->menuUrl(), [])->assertStatus(405);
        $this->putJson($this->menuUrl(), [])->assertStatus(405);
        $this->deleteJson($this->menuUrl())->assertStatus(405);
    }

    public function test_public_routes_do_not_grant_admin_access(): void
    {
        $this->getJson('/api/v1/categories')->assertStatus(401);
        $this->getJson('/api/v1/tables')->assertStatus(401);
        $this->getJson('/api/v1/menu-items')->assertStatus(401);
    }

    // ------------------------------------------------------------ rate limiting

    public function test_public_api_is_rate_limited(): void
    {
        RateLimiter::clear('public-menu');
        $last = null;

        for ($i = 0; $i < 125; $i++) {
            $last = $this->getJson($this->menuUrl('unknown'));
            if ($last->getStatusCode() === 429) {
                break;
            }
        }

        $last->assertStatus(429)->assertJsonPath('success', false);
    }
}
