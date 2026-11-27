<?php

namespace Tests\Feature\Settings;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestaurantSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Golden Spoon',
            'description' => 'Fine food',
            'address' => '1 Main St',
            'phone' => '+1 (555) 010-0100',
            'currency' => 'USD',
        ], $overrides);
    }

    private function multipartPut(array $data)
    {
        return $this->post('/api/v1/settings/restaurant', $data + ['_method' => 'PUT'], ['Accept' => 'application/json']);
    }

    public function test_guest_and_non_admin_are_rejected(): void
    {
        Restaurant::factory()->create(['name' => 'Original']);

        $this->getJson('/api/v1/settings/restaurant')->assertStatus(401);
        $this->putJson('/api/v1/settings/restaurant', $this->payload())->assertStatus(401);

        $user = User::factory()->create();
        $user->forceFill(['role' => 'guest'])->save();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/settings/restaurant')->assertStatus(403);
        $this->putJson('/api/v1/settings/restaurant', $this->payload())->assertStatus(403);
        $this->assertSame('Original', Restaurant::first()->name);
    }

    public function test_show_returns_current_restaurant(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create(['name' => 'Golden Spoon', 'currency' => 'EUR']);

        $this->getJson('/api/v1/settings/restaurant')->assertOk()
            ->assertJsonPath('data.restaurant.name', 'Golden Spoon')
            ->assertJsonPath('data.restaurant.currency', 'EUR')
            ->assertJsonPath('data.restaurant.logo_url', null);
    }

    public function test_show_returns_null_when_no_restaurant_exists(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/settings/restaurant')->assertOk()->assertJsonPath('data.restaurant', null);
    }

    public function test_update_changes_profile_fields(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();

        $this->putJson('/api/v1/settings/restaurant', $this->payload(['name' => 'Renamed', 'currency' => 'mmk']))->assertOk()
            ->assertJsonPath('data.restaurant.name', 'Renamed')
            ->assertJsonPath('data.restaurant.currency', 'MMK');

        $this->assertSame(1, Restaurant::count());
        $this->assertSame('1 Main St', Restaurant::first()->address);
    }

    public function test_update_creates_the_restaurant_on_a_fresh_install_but_never_a_second(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/v1/settings/restaurant', $this->payload())->assertOk();
        $this->putJson('/api/v1/settings/restaurant', $this->payload(['name' => 'Second Save']))->assertOk();

        $this->assertSame(1, Restaurant::count());
        $this->assertSame('Second Save', Restaurant::first()->name);
    }

    public function test_validation(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();

        $this->putJson('/api/v1/settings/restaurant', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'currency'], 'errors');
        $this->putJson('/api/v1/settings/restaurant', $this->payload(['currency' => 'DOLLARS']))->assertStatus(422)->assertJsonValidationErrors(['currency'], 'errors');
        $this->putJson('/api/v1/settings/restaurant', $this->payload(['currency' => '$']))->assertStatus(422);
        $this->putJson('/api/v1/settings/restaurant', $this->payload(['phone' => 'call <script>']))->assertStatus(422)->assertJsonValidationErrors(['phone'], 'errors');
        $this->putJson('/api/v1/settings/restaurant', $this->payload(['name' => str_repeat('x', 256)]))->assertStatus(422);
    }

    public function test_logo_upload_stores_safe_file_and_returns_url(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();

        $response = $this->multipartPut($this->payload(['logo' => UploadedFile::fake()->image('../../evil.png', 300, 300)]))->assertOk();

        $path = Restaurant::first()->logo;
        $this->assertMatchesRegularExpression('#^restaurant/[0-9a-f-]{36}\.png$#', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString($path, $response->json('data.restaurant.logo_url'));
    }

    public function test_invalid_logo_files_are_rejected(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();

        $path = tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($path, '<?php echo 1;');
        $php = new UploadedFile($path, 'logo.png', null, null, true);

        $this->multipartPut($this->payload(['logo' => $php]))->assertStatus(422)->assertJsonValidationErrors(['logo'], 'errors');
        $this->multipartPut($this->payload(['logo' => UploadedFile::fake()->image('big.png')->size(3000)]))->assertStatus(422);

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertNull(Restaurant::first()->logo);
    }

    public function test_logo_replacement_and_removal_delete_old_files(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();

        $this->multipartPut($this->payload(['logo' => UploadedFile::fake()->image('a.jpg')]))->assertOk();
        $first = Restaurant::first()->logo;

        $this->multipartPut($this->payload(['logo' => UploadedFile::fake()->image('b.png')]))->assertOk();
        $second = Restaurant::first()->logo;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->putJson('/api/v1/settings/restaurant', $this->payload(['remove_logo' => true]))->assertOk()
            ->assertJsonPath('data.restaurant.logo_url', null);
        $this->assertNull(Restaurant::first()->logo);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_saving_without_a_logo_keeps_the_existing_one(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();
        $this->multipartPut($this->payload(['logo' => UploadedFile::fake()->image('a.jpg')]))->assertOk();
        $path = Restaurant::first()->logo;

        $this->putJson('/api/v1/settings/restaurant', $this->payload(['name' => 'Other']))->assertOk();

        $this->assertSame($path, Restaurant::first()->logo);
        Storage::disk('public')->assertExists($path);
    }

    public function test_changes_appear_on_the_public_menu_without_new_qr(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create(['name' => 'Old Name', 'currency' => 'USD']);
        $table = RestaurantTable::factory()->create();
        $category = Category::factory()->create();
        MenuItem::factory()->create(['category_id' => $category->id]);

        $this->putJson('/api/v1/settings/restaurant', $this->payload(['name' => 'New Name', 'currency' => 'EUR']))->assertOk();

        $this->getJson('/api/v1/public/menu/'.$table->qr_token)->assertOk()
            ->assertJsonPath('data.restaurant.name', 'New Name')
            ->assertJsonPath('data.restaurant.currency', 'EUR');
    }

    public function test_deactivating_the_restaurant_blocks_the_public_menu(): void
    {
        $this->actingAsAdmin();
        Restaurant::factory()->create();
        $table = RestaurantTable::factory()->create();

        $this->putJson('/api/v1/settings/restaurant', $this->payload(['is_active' => false]))->assertOk();

        $this->getJson('/api/v1/public/menu/'.$table->qr_token)->assertStatus(404);
    }
}
