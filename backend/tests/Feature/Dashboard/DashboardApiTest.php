<?php

namespace Tests\Feature\Dashboard;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/dashboard')->assertStatus(401);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'guest'])->save();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard')->assertStatus(403);
    }

    public function test_empty_database_returns_zero_counts(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/dashboard')->assertOk()
            ->assertJsonPath('data.stats.total_categories', 0)
            ->assertJsonPath('data.stats.total_menu_items', 0)
            ->assertJsonPath('data.stats.total_tables', 0)
            ->assertJsonCount(0, 'data.recent_menu_updates');
    }

    public function test_stats_are_accurate_and_ignore_soft_deleted_rows(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Restaurant::factory()->create(['name' => 'Golden Spoon', 'currency' => 'EUR']);
        $category = Category::factory()->create();
        Category::factory()->create()->delete();
        MenuItem::factory()->count(3)->create(['category_id' => $category->id]);
        MenuItem::factory()->count(2)->unavailable()->create(['category_id' => $category->id]);
        MenuItem::factory()->create(['category_id' => $category->id])->delete();
        RestaurantTable::factory()->count(2)->create();
        RestaurantTable::factory()->inactive()->create();

        $this->getJson('/api/v1/dashboard')->assertOk()
            ->assertJsonPath('data.stats', [
                'total_categories' => 1,
                'total_menu_items' => 5,
                'available_items' => 3,
                'unavailable_items' => 2,
                'total_tables' => 3,
                'active_qr_codes' => 2,
            ])
            ->assertJsonPath('data.restaurant.name', 'Golden Spoon')
            ->assertJsonPath('data.restaurant.currency', 'EUR');
    }

    public function test_recent_updates_are_newest_first_and_capped(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $old = MenuItem::factory()->create(['category_id' => $category->id, 'name' => 'Old']);
        MenuItem::factory()->count(9)->create(['category_id' => $category->id]);
        $old->forceFill(['updated_at' => now()->addMinute()])->save();

        $response = $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonCount(8, 'data.recent_menu_updates');

        $this->assertSame('Old', $response->json('data.recent_menu_updates.0.name'));
    }
}
