<?php

namespace Tests\Feature\Table;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\QrCodeService;
use App\Services\TableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TableApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    // ---------------------------------------------------------------- CRUD

    public function test_create_tables_generate_distinct_qr_tokens(): void
    {
        $this->actingAsAdmin();

        $first = $this->postJson('/api/v1/tables', ['table_number' => 'Table 01'])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.table_number', 'Table 01')
            ->assertJsonPath('data.is_active', true);
        $second = $this->postJson('/api/v1/tables', ['table_number' => 'Table 02'])->assertStatus(201);

        $tokenOne = RestaurantTable::where('table_number', 'Table 01')->value('qr_token');
        $tokenTwo = RestaurantTable::where('table_number', 'Table 02')->value('qr_token');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{48}$/', $tokenOne);
        $this->assertNotSame($tokenOne, $tokenTwo);
        $this->assertSame(config('app.frontend_url').'/menu/'.$tokenOne, $first->json('data.menu_url'));
        $this->assertNotSame($first->json('data.menu_url'), $second->json('data.menu_url'));
    }

    public function test_token_cannot_be_supplied_by_client(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/tables', ['table_number' => 'T9', 'qr_token' => 'attacker-token'])->assertStatus(201);

        $this->assertNotSame('attacker-token', RestaurantTable::where('table_number', 'T9')->value('qr_token'));
    }

    public function test_responses_never_expose_the_raw_token_field(): void
    {
        $this->actingAsAdmin();
        RestaurantTable::factory()->create();

        $row = $this->getJson('/api/v1/tables')->assertOk()->json('data.0');

        $this->assertArrayNotHasKey('qr_token', $row);
        $this->assertArrayHasKey('menu_url', $row);
    }

    public function test_duplicate_table_number_is_a_validation_error(): void
    {
        $this->actingAsAdmin();
        RestaurantTable::factory()->create(['table_number' => 'Table 01']);

        $this->postJson('/api/v1/tables', ['table_number' => 'Table 01'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['table_number'], 'errors');
        $this->postJson('/api/v1/tables', ['table_number' => '  Table 01  '])->assertStatus(422);
        $this->assertSame(1, RestaurantTable::count());
    }

    public function test_create_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/tables', [])->assertStatus(422)->assertJsonValidationErrors(['table_number'], 'errors');
        $this->postJson('/api/v1/tables', ['table_number' => str_repeat('x', 51)])->assertStatus(422);
    }

    public function test_update_table_number_keeps_the_token(): void
    {
        $this->actingAsAdmin();
        $table = RestaurantTable::factory()->create(['table_number' => 'A1']);
        $token = $table->qr_token;

        $this->putJson("/api/v1/tables/{$table->id}", ['table_number' => 'A2'])
            ->assertOk()
            ->assertJsonPath('data.table_number', 'A2');

        $this->assertSame($token, $table->fresh()->qr_token);
    }

    public function test_update_to_existing_number_is_rejected_but_own_number_allowed(): void
    {
        $this->actingAsAdmin();
        RestaurantTable::factory()->create(['table_number' => 'A1']);
        $b = RestaurantTable::factory()->create(['table_number' => 'B1']);

        $this->putJson("/api/v1/tables/{$b->id}", ['table_number' => 'A1'])->assertStatus(422);
        $this->putJson("/api/v1/tables/{$b->id}", ['table_number' => 'B1'])->assertOk();
    }

    public function test_delete_table(): void
    {
        $this->actingAsAdmin();
        $table = RestaurantTable::factory()->create();

        $this->deleteJson("/api/v1/tables/{$table->id}")->assertOk();

        $this->assertDatabaseMissing('restaurant_tables', ['id' => $table->id]);
    }

    public function test_list_search_filter_and_pagination(): void
    {
        $this->actingAsAdmin();
        foreach (range(1, 12) as $n) {
            RestaurantTable::factory()->create(['table_number' => sprintf('Table %02d', $n)]);
        }
        RestaurantTable::factory()->inactive()->create(['table_number' => 'Patio']);

        $this->getJson('/api/v1/tables?per_page=5&page=3')->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('meta.total', 13);
        $this->getJson('/api/v1/tables?search=patio')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/tables?is_active=0')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.table_number', 'Patio');
        $this->getJson('/api/v1/tables?search=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_activate_and_deactivate_table(): void
    {
        $this->actingAsAdmin();
        $table = RestaurantTable::factory()->create();

        $this->patchJson("/api/v1/tables/{$table->id}/status", ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertFalse($table->fresh()->is_active);

        $this->patchJson("/api/v1/tables/{$table->id}/status", ['is_active' => true])->assertOk();
        $this->assertTrue($table->fresh()->is_active);

        $this->patchJson("/api/v1/tables/{$table->id}/status", [])->assertStatus(422);
    }

    // ---------------------------------------------------------------- QR

    public function test_qr_preview_returns_url_and_svg(): void
    {
        $this->actingAsAdmin();
        $table = RestaurantTable::factory()->create(['table_number' => 'Table 01']);

        $response = $this->getJson("/api/v1/tables/{$table->id}/qr")->assertOk();

        $this->assertSame($table->menuUrl(), $response->json('data.menu_url'));
        $this->assertStringContainsString('<svg', $response->json('data.qr_svg'));
    }

    public function test_download_returns_a_valid_png_that_decodes_to_the_menu_url(): void
    {
        $this->actingAsAdmin();
        $table = RestaurantTable::factory()->create(['table_number' => 'Table 01']);

        $response = $this->get("/api/v1/tables/{$table->id}/qr/download")->assertOk();

        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="qr-table-01.png"', $response->headers->get('Content-Disposition'));

        $png = $response->getContent();
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));

        $info = getimagesizefromstring($png);
        $this->assertNotFalse($info);
        $this->assertSame(IMAGETYPE_PNG, $info[2]);
        $this->assertGreaterThanOrEqual(300, $info[0]);
        $this->assertSame($info[0], $info[1]);
    }

    public function test_png_encodes_the_current_token_url(): void
    {
        $table = RestaurantTable::factory()->create();
        $service = app(QrCodeService::class);

        $this->assertNotSame($service->png($table->menuUrl()), $service->png('https://example.com/other'));
        $this->assertSame($service->png($table->menuUrl()), $service->png($table->menuUrl()));
    }

    public function test_print_data_has_restaurant_name_table_number_and_qr(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create(['name' => 'Golden Spoon']);
        $table = RestaurantTable::factory()->create(['table_number' => 'Table 07']);

        $this->getJson("/api/v1/tables/{$table->id}/qr/print")
            ->assertOk()
            ->assertJsonPath('data.restaurant_name', 'Golden Spoon')
            ->assertJsonPath('data.table_number', 'Table 07')
            ->assertJsonPath('data.menu_url', $table->menuUrl())
            ->assertJsonStructure(['data' => ['qr_svg']]);
    }

    public function test_regenerate_invalidates_the_old_token(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();
        $table = RestaurantTable::factory()->create();
        $oldToken = $table->qr_token;
        $oldUrl = $table->menuUrl();
        $service = app(TableService::class);
        $this->assertNotNull($service->resolvePublicTable($oldToken));

        $response = $this->postJson("/api/v1/tables/{$table->id}/qr/regenerate")->assertOk();

        $newToken = $table->fresh()->qr_token;
        $this->assertNotSame($oldToken, $newToken);
        $this->assertNotSame($oldUrl, $response->json('data.menu_url'));
        $this->assertNull($service->resolvePublicTable($oldToken));
        $this->assertTrue($service->resolvePublicTable($newToken)->is($table));
    }

    public function test_url_is_stable_across_edits_and_status_changes(): void
    {
        $this->actingAsAdmin();
        $table = RestaurantTable::factory()->create();
        $url = $table->menuUrl();

        $this->putJson("/api/v1/tables/{$table->id}", ['table_number' => 'Renamed'])->assertOk();
        $this->patchJson("/api/v1/tables/{$table->id}/status", ['is_active' => false])->assertOk();
        $this->patchJson("/api/v1/tables/{$table->id}/status", ['is_active' => true])->assertOk();

        $this->assertSame($url, $table->fresh()->menuUrl());
    }

    // ---------------------------------------------------------------- authorization

    public function test_guest_cannot_use_table_or_qr_endpoints(): void
    {
        $table = RestaurantTable::factory()->create();
        $token = $table->qr_token;

        $this->getJson('/api/v1/tables')->assertStatus(401);
        $this->postJson('/api/v1/tables', ['table_number' => 'X'])->assertStatus(401);
        $this->putJson("/api/v1/tables/{$table->id}", ['table_number' => 'X'])->assertStatus(401);
        $this->patchJson("/api/v1/tables/{$table->id}/status", ['is_active' => false])->assertStatus(401);
        $this->deleteJson("/api/v1/tables/{$table->id}")->assertStatus(401);
        $this->getJson("/api/v1/tables/{$table->id}/qr")->assertStatus(401);
        $this->getJson("/api/v1/tables/{$table->id}/qr/print")->assertStatus(401);
        $this->getJson("/api/v1/tables/{$table->id}/qr/download")->assertStatus(401);
        $this->postJson("/api/v1/tables/{$table->id}/qr/regenerate")->assertStatus(401);

        $table->refresh();
        $this->assertSame($token, $table->qr_token);
        $this->assertTrue($table->is_active);
    }

    public function test_non_admin_cannot_regenerate_or_manage_tables(): void
    {
        $table = RestaurantTable::factory()->create();
        $token = $table->qr_token;
        $user = User::factory()->create();
        $user->forceFill(['role' => 'guest'])->save();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tables/{$table->id}/qr/regenerate")->assertStatus(403);
        $this->getJson("/api/v1/tables/{$table->id}/qr/download")->assertStatus(403);
        $this->getJson("/api/v1/tables/{$table->id}/qr")->assertStatus(403);
        $this->patchJson("/api/v1/tables/{$table->id}/status", ['is_active' => false])->assertStatus(403);
        $this->deleteJson("/api/v1/tables/{$table->id}")->assertStatus(403);
        $this->postJson('/api/v1/tables', ['table_number' => 'Z'])->assertStatus(403);

        $this->assertSame($token, $table->fresh()->qr_token);
        $this->assertDatabaseHas('restaurant_tables', ['id' => $table->id, 'is_active' => true]);
    }

    public function test_unknown_table_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/tables/9999/qr')->assertStatus(404)->assertJsonPath('success', false);
        $this->postJson('/api/v1/tables/9999/qr/regenerate')->assertStatus(404);
    }

    // ---------------------------------------------------------------- public token validation (service level)

    public function test_public_resolution_accepts_valid_active_token(): void
    {
        Restaurant::factory()->create();
        $table = RestaurantTable::factory()->create();

        $this->assertTrue(app(TableService::class)->resolvePublicTable($table->qr_token)->is($table));
    }

    public function test_public_resolution_rejects_invalid_tokens(): void
    {
        Restaurant::factory()->create();
        RestaurantTable::factory()->create();
        $service = app(TableService::class);

        $this->assertNull($service->resolvePublicTable('does-not-exist'));
        $this->assertNull($service->resolvePublicTable(str_repeat('a', 48)));
        $this->assertNull($service->resolvePublicTable("1' OR '1'='1"));
        $this->assertNull($service->resolvePublicTable('1'));
        $this->assertNull($service->resolvePublicTable(''));
    }

    public function test_public_resolution_rejects_inactive_table(): void
    {
        Restaurant::factory()->create();
        $table = RestaurantTable::factory()->inactive()->create();

        $this->assertNull(app(TableService::class)->resolvePublicTable($table->qr_token));
    }

    public function test_deactivating_a_table_rejects_public_access_and_reactivating_restores_it(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();
        $table = RestaurantTable::factory()->create();
        $token = $table->qr_token;
        $service = app(TableService::class);

        $this->patchJson("/api/v1/tables/{$table->id}/status", ['is_active' => false])->assertOk();
        $this->assertNull($service->resolvePublicTable($token));

        $this->patchJson("/api/v1/tables/{$table->id}/status", ['is_active' => true])->assertOk();
        $this->assertNotNull($service->resolvePublicTable($token));
    }

    public function test_public_resolution_rejects_when_restaurant_inactive_or_missing(): void
    {
        $table = RestaurantTable::factory()->create();
        $service = app(TableService::class);

        $this->assertNull($service->resolvePublicTable($table->qr_token));

        Restaurant::factory()->inactive()->create();
        $this->assertNull($service->resolvePublicTable($table->qr_token));
    }

    public function test_deleted_table_token_no_longer_resolves(): void
    {
        Restaurant::factory()->create();
        $table = RestaurantTable::factory()->create();
        $token = $table->qr_token;
        $table->delete();

        $this->assertNull(app(TableService::class)->resolvePublicTable($token));
    }
}
