<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_guest_cannot_access_category_endpoints(): void
    {
        $category = Category::factory()->create();

        $this->getJson('/api/v1/categories')->assertStatus(401)->assertJsonPath('success', false);
        $this->postJson('/api/v1/categories', ['name' => 'X'])->assertStatus(401);
        $this->putJson("/api/v1/categories/{$category->id}", ['name' => 'Y'])->assertStatus(401);
        $this->deleteJson("/api/v1/categories/{$category->id}")->assertStatus(401);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => $category->name]);
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'guest'])->save();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/categories')->assertStatus(403)->assertJsonPath('success', false);
        $this->postJson('/api/v1/categories', ['name' => 'X'])->assertStatus(403);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_create_category(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/categories', ['name' => 'Soups', 'description' => 'Hot soups'])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Soups')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('categories', ['name' => 'Soups']);
    }

    public function test_create_category_validates_input(): void
    {
        $this->actingAsAdmin();
        Category::factory()->create(['name' => 'Rice']);

        $this->postJson('/api/v1/categories', [])->assertStatus(422)->assertJsonValidationErrors(['name'], 'errors');
        $this->postJson('/api/v1/categories', ['name' => 'Rice'])->assertStatus(422)->assertJsonValidationErrors(['name'], 'errors');
    }

    public function test_new_category_gets_next_sort_order(): void
    {
        $this->actingAsAdmin();
        Category::factory()->create(['sort_order' => 10]);

        $this->postJson('/api/v1/categories', ['name' => 'Last'])->assertStatus(201)->assertJsonPath('data.sort_order', 11);
    }

    public function test_update_category_and_toggle_active(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['name' => 'Old', 'is_active' => true]);

        $this->putJson("/api/v1/categories/{$category->id}", ['name' => 'New', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.name', 'New')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New', 'is_active' => false]);
    }

    public function test_update_allows_keeping_own_name(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['name' => 'Same']);

        $this->putJson("/api/v1/categories/{$category->id}", ['name' => 'Same', 'description' => 'changed'])->assertOk();
    }

    public function test_delete_empty_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $this->deleteJson("/api/v1/categories/{$category->id}")->assertOk()->assertJsonPath('success', true);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_delete_category_with_items_is_rejected(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        MenuItem::factory()->create(['category_id' => $category->id]);

        $this->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertNotSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_deleted_category_returns_404(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $category->delete();

        $this->getJson("/api/v1/categories/{$category->id}")->assertStatus(404);
    }

    public function test_list_is_paginated(): void
    {
        $this->actingAsAdmin();
        Category::factory()->count(25)->create();

        $this->getJson('/api/v1/categories?per_page=10&page=2')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_per_page_is_capped(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/categories?per_page=1000')->assertStatus(422);
    }

    public function test_search_and_active_filter(): void
    {
        $this->actingAsAdmin();
        Category::factory()->create(['name' => 'Spicy Noodles']);
        Category::factory()->create(['name' => 'Drinks']);
        Category::factory()->inactive()->create(['name' => 'Spicy Soup']);

        $this->getJson('/api/v1/categories?search=spicy')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/categories?search=spicy&is_active=1')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Spicy Noodles');
        $this->getJson('/api/v1/categories?is_active=0')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->actingAsAdmin();
        Category::factory()->create(['name' => 'Rice']);

        $this->getJson('/api/v1/categories?search=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_sorting(): void
    {
        $this->actingAsAdmin();
        Category::factory()->create(['name' => 'B', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'A', 'sort_order' => 2]);

        $this->getJson('/api/v1/categories?sort_by=name&sort_dir=asc')->assertOk()->assertJsonPath('data.0.name', 'A');
        $this->getJson('/api/v1/categories?sort_by=sort_order&sort_dir=asc')->assertOk()->assertJsonPath('data.0.name', 'B');
        $this->getJson('/api/v1/categories?sort_by=password')->assertStatus(422);
    }

    public function test_list_includes_menu_item_counts(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        MenuItem::factory()->count(3)->create(['category_id' => $category->id]);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonPath('data.0.menu_items_count', 3);
    }

    public function test_reorder_categories(): void
    {
        $this->actingAsAdmin();
        $a = Category::factory()->create(['sort_order' => 1]);
        $b = Category::factory()->create(['sort_order' => 2]);
        $c = Category::factory()->create(['sort_order' => 3]);

        $this->postJson('/api/v1/categories/reorder', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

        $this->assertSame(1, $c->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame(3, $b->fresh()->sort_order);
    }

    public function test_reorder_rejects_unknown_ids(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/categories/reorder', ['ids' => [9999]])->assertStatus(422);
    }

    public function test_mass_assignment_of_unknown_fields_is_ignored(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/categories', ['name' => 'Safe', 'id' => 999, 'deleted_at' => now()->toDateTimeString()])
            ->assertStatus(201);

        $category = Category::where('name', 'Safe')->first();
        $this->assertNotSame(999, $category->id);
        $this->assertNull($category->deleted_at);
    }
}
