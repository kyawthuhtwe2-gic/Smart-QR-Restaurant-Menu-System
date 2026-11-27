<?php

namespace Tests\Feature\MenuItem;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MenuItemApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
    }

    /**
     * Real temp file (not Laravel's fake, which trusts the filename) so MIME sniffing is exercised.
     */
    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'name' => 'Pad Thai',
            'description' => 'Noodles',
            'price' => '9.50',
        ], $overrides);
    }

    public function test_create_menu_item(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/v1/menu-items', $this->payload($category))
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Pad Thai')
            ->assertJsonPath('data.price', '9.50')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.image_url', null);

        $this->assertDatabaseHas('menu_items', ['name' => 'Pad Thai', 'price' => '9.50']);
    }

    public function test_create_validation(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/v1/menu-items', [])->assertStatus(422)->assertJsonValidationErrors(['category_id', 'name', 'price'], 'errors');
        $this->postJson('/api/v1/menu-items', $this->payload($category, ['price' => '-1']))->assertStatus(422)->assertJsonValidationErrors(['price'], 'errors');
        $this->postJson('/api/v1/menu-items', $this->payload($category, ['price' => '1.999']))->assertStatus(422)->assertJsonValidationErrors(['price'], 'errors');
        $this->postJson('/api/v1/menu-items', $this->payload($category, ['price' => 'abc']))->assertStatus(422);
        $this->postJson('/api/v1/menu-items', $this->payload($category, ['category_id' => 9999]))->assertStatus(422)->assertJsonValidationErrors(['category_id'], 'errors');
    }

    public function test_cannot_assign_item_to_deleted_category(): void
    {
        $category = Category::factory()->create();
        $category->delete();

        $this->postJson('/api/v1/menu-items', $this->payload($category))->assertStatus(422);
    }

    public function test_upload_valid_image(): void
    {
        $category = Category::factory()->create();

        $response = $this->post('/api/v1/menu-items', $this->payload($category) + [
            'image' => UploadedFile::fake()->image('../../evil name.png', 600, 400),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $path = MenuItem::first()->image_path;
        $this->assertStringStartsWith('menu-items/', $path);
        $this->assertStringNotContainsString('evil', $path);
        $this->assertMatchesRegularExpression('#^menu-items/[0-9a-f-]{36}\.png$#', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertNotNull($response->json('data.image_url'));
        $this->assertStringContainsString($path, $response->json('data.image_url'));
    }

    public function test_upload_invalid_files_are_rejected(): void
    {
        $category = Category::factory()->create();

        $cases = [
            'php script' => $this->realUpload('shell.php', '<?php echo 1;'),
            'php disguised as jpg' => $this->realUpload('shell.jpg', '<?php echo 1;'),
            'text disguised as png' => $this->realUpload('a.png', 'not really an image'),
            'html disguised as jpg' => $this->realUpload('page.jpg', '<html><script>alert(1)</script></html>'),
            'pdf' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf'),
            'svg' => $this->realUpload('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'svg disguised as png' => $this->realUpload('x.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'too large' => UploadedFile::fake()->image('big.jpg')->size(3000),
        ];

        foreach ($cases as $label => $file) {
            $response = $this->post('/api/v1/menu-items', $this->payload($category) + ['image' => $file], ['Accept' => 'application/json']);
            $this->assertSame(422, $response->getStatusCode(), "Case [{$label}] should be rejected");
            $response->assertJsonValidationErrors(['image'], 'errors');
        }

        $this->assertDatabaseCount('menu_items', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_update_menu_item_and_price(): void
    {
        $item = MenuItem::factory()->create(['price' => '5.00']);

        $this->putJson("/api/v1/menu-items/{$item->id}", ['name' => 'Renamed', 'price' => '6.25'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed')
            ->assertJsonPath('data.price', '6.25');

        $this->assertSame('6.25', $item->fresh()->price);
    }

    public function test_image_replacement_deletes_old_file(): void
    {
        $item = MenuItem::factory()->create();

        $this->post("/api/v1/menu-items/{$item->id}", ['_method' => 'PUT', 'image' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertOk();
        $first = $item->fresh()->image_path;
        Storage::disk('public')->assertExists($first);

        $this->post("/api/v1/menu-items/{$item->id}", ['_method' => 'PUT', 'image' => UploadedFile::fake()->image('b.png')], ['Accept' => 'application/json'])->assertOk();
        $second = $item->fresh()->image_path;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_remove_image_flag_deletes_file(): void
    {
        $item = MenuItem::factory()->create();
        $this->post("/api/v1/menu-items/{$item->id}", ['_method' => 'PUT', 'image' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json']);
        $path = $item->fresh()->image_path;

        $this->putJson("/api/v1/menu-items/{$item->id}", ['remove_image' => true])->assertOk()->assertJsonPath('data.image_url', null);

        $this->assertNull($item->fresh()->image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_failed_validation_keeps_existing_image(): void
    {
        $item = MenuItem::factory()->create();
        $this->post("/api/v1/menu-items/{$item->id}", ['_method' => 'PUT', 'image' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json']);
        $path = $item->fresh()->image_path;

        $this->post("/api/v1/menu-items/{$item->id}", ['_method' => 'PUT', 'image' => UploadedFile::fake()->createWithContent('x.php', '<?php')], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertSame($path, $item->fresh()->image_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_delete_item_removes_image_and_soft_deletes(): void
    {
        $item = MenuItem::factory()->create();
        $this->post("/api/v1/menu-items/{$item->id}", ['_method' => 'PUT', 'image' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json']);
        $path = $item->fresh()->image_path;

        $this->deleteJson("/api/v1/menu-items/{$item->id}")->assertOk();

        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_update_availability(): void
    {
        $item = MenuItem::factory()->create(['is_available' => true]);

        $this->patchJson("/api/v1/menu-items/{$item->id}/availability", ['is_available' => false])
            ->assertOk()
            ->assertJsonPath('data.is_available', false);
        $this->assertFalse($item->fresh()->is_available);

        $this->patchJson("/api/v1/menu-items/{$item->id}/availability", ['is_available' => true])->assertOk();
        $this->assertTrue($item->fresh()->is_available);

        $this->patchJson("/api/v1/menu-items/{$item->id}/availability", [])->assertStatus(422);
    }

    public function test_featured_flag_and_filter(): void
    {
        MenuItem::factory()->featured()->create();
        MenuItem::factory()->count(2)->create();

        $this->getJson('/api/v1/menu-items?is_featured=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.is_featured', true);
        $this->getJson('/api/v1/menu-items?is_featured=0')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_filter_by_category(): void
    {
        $rice = Category::factory()->create();
        $drinks = Category::factory()->create();
        MenuItem::factory()->count(2)->create(['category_id' => $rice->id]);
        MenuItem::factory()->create(['category_id' => $drinks->id]);

        $this->getJson("/api/v1/menu-items?category_id={$rice->id}")->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/menu-items?category_id={$drinks->id}")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_by_name(): void
    {
        MenuItem::factory()->create(['name' => 'Chicken Curry']);
        MenuItem::factory()->create(['name' => 'Beef Noodle']);

        $this->getJson('/api/v1/menu-items?search=curry')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Chicken Curry');
    }

    public function test_filter_by_price_range(): void
    {
        MenuItem::factory()->create(['price' => '2.00']);
        MenuItem::factory()->create(['price' => '8.00']);
        MenuItem::factory()->create(['price' => '20.00']);

        $this->getJson('/api/v1/menu-items?min_price=5&max_price=10')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.price', '8.00');
        $this->getJson('/api/v1/menu-items?min_price=8')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/menu-items?min_price=10&max_price=5')->assertStatus(422);
    }

    public function test_filter_by_availability(): void
    {
        MenuItem::factory()->count(2)->create();
        MenuItem::factory()->unavailable()->create();

        $this->getJson('/api/v1/menu-items?is_available=1')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/menu-items?is_available=0')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_sort_by_price(): void
    {
        MenuItem::factory()->create(['price' => '5.00']);
        MenuItem::factory()->create(['price' => '1.00']);
        MenuItem::factory()->create(['price' => '3.00']);

        $this->getJson('/api/v1/menu-items?sort_by=price&sort_dir=asc')->assertOk()
            ->assertJsonPath('data.0.price', '1.00')->assertJsonPath('data.2.price', '5.00');
        $this->getJson('/api/v1/menu-items?sort_by=price&sort_dir=desc')->assertOk()->assertJsonPath('data.0.price', '5.00');
    }

    public function test_pagination(): void
    {
        MenuItem::factory()->count(12)->create();

        $this->getJson('/api/v1/menu-items?per_page=5&page=3')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_response_exposes_no_internal_fields(): void
    {
        MenuItem::factory()->create(['image_path' => null]);

        $row = $this->getJson('/api/v1/menu-items')->assertOk()->json('data.0');

        $this->assertArrayNotHasKey('image_path', $row);
        $this->assertArrayNotHasKey('deleted_at', $row);
    }
}
