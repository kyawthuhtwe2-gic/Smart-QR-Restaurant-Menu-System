<?php

namespace Tests\Feature\MenuItem;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MenuItemAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_menu_item_endpoints(): void
    {
        $item = MenuItem::factory()->create(['price' => '5.00']);

        $this->getJson('/api/v1/menu-items')->assertStatus(401)->assertJsonPath('success', false);
        $this->getJson("/api/v1/menu-items/{$item->id}")->assertStatus(401);
        $this->postJson('/api/v1/menu-items', ['name' => 'X'])->assertStatus(401);
        $this->putJson("/api/v1/menu-items/{$item->id}", ['price' => '1.00'])->assertStatus(401);
        $this->patchJson("/api/v1/menu-items/{$item->id}/availability", ['is_available' => false])->assertStatus(401);
        $this->deleteJson("/api/v1/menu-items/{$item->id}")->assertStatus(401);

        $item->refresh();
        $this->assertSame('5.00', $item->price);
        $this->assertTrue($item->is_available);
        $this->assertNull($item->deleted_at);
    }

    public function test_non_admin_role_is_forbidden(): void
    {
        Storage::fake('public');
        $item = MenuItem::factory()->create();
        $user = User::factory()->create();
        $user->forceFill(['role' => 'guest'])->save();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/menu-items')->assertStatus(403);
        $this->putJson("/api/v1/menu-items/{$item->id}", ['name' => 'Hacked'])->assertStatus(403);
        $this->patchJson("/api/v1/menu-items/{$item->id}/availability", ['is_available' => false])->assertStatus(403);
        $this->deleteJson("/api/v1/menu-items/{$item->id}")->assertStatus(403);

        $this->assertNotSame('Hacked', $item->fresh()->name);
        $this->assertNull($item->fresh()->deleted_at);
    }
}
