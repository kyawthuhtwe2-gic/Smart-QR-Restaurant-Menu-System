<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    private function configureAdmin(string $password = 'seed-admin-pass-1'): void
    {
        config([
            'admin.name' => 'Seed Admin',
            'admin.email' => 'seed-admin@example.com',
            'admin.password' => $password,
        ]);
    }

    public function test_database_seeder_creates_expected_data(): void
    {
        $this->configureAdmin();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertSame(1, Restaurant::count());
        $this->assertEqualsCanonicalizing(
            ['Rice', 'Noodles', 'Chicken', 'Drinks', 'Desserts'],
            Category::pluck('name')->all(),
        );
        $this->assertGreaterThan(0, MenuItem::count());
        $this->assertEqualsCanonicalizing(
            ['Table 01', 'Table 02', 'Table 03', 'Table 04', 'Table 05'],
            RestaurantTable::pluck('table_number')->all(),
        );
        $this->assertSame(5, RestaurantTable::distinct('qr_token')->count('qr_token'));
    }

    public function test_seeding_twice_creates_no_duplicates_and_keeps_tokens(): void
    {
        $this->configureAdmin();

        $this->seed(DatabaseSeeder::class);
        $counts = [User::count(), Restaurant::count(), Category::count(), MenuItem::count(), RestaurantTable::count()];
        $tokens = RestaurantTable::orderBy('id')->pluck('qr_token')->all();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [User::count(), Restaurant::count(), Category::count(), MenuItem::count(), RestaurantTable::count()]);
        $this->assertSame($tokens, RestaurantTable::orderBy('id')->pluck('qr_token')->all());
    }

    public function test_reseeding_does_not_overwrite_admin_password_or_edited_data(): void
    {
        $this->configureAdmin();
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'seed-admin@example.com')->first();
        $admin->update(['password' => 'changed-by-admin-9']);
        MenuItem::where('name', 'Pad Thai')->update(['price' => '99.00']);
        Restaurant::first()->update(['name' => 'Renamed']);

        $this->configureAdmin('different-env-password');
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('changed-by-admin-9', $admin->fresh()->password));
        $this->assertSame('99.00', MenuItem::where('name', 'Pad Thai')->first()->price);
        $this->assertSame('Renamed', Restaurant::first()->name);
    }

    public function test_soft_deleted_seed_category_is_not_recreated(): void
    {
        $this->configureAdmin();
        $this->seed(DatabaseSeeder::class);

        Category::where('name', 'Rice')->first()->menuItems()->delete();
        Category::where('name', 'Rice')->first()->delete();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Category::withTrashed()->where('name', 'Rice')->count());
        $this->assertNull(Category::where('name', 'Rice')->first());
    }

    public function test_seeded_admin_can_authenticate(): void
    {
        $this->configureAdmin('seed-admin-pass-1');
        $this->seed(DatabaseSeeder::class);

        $this->postJson('/api/v1/auth/login', ['email' => 'seed-admin@example.com', 'password' => 'seed-admin-pass-1'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_admin_seeder_skips_creation_without_password(): void
    {
        $this->configureAdmin('');

        $this->seed(AdminSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_no_hardcoded_admin_password_in_seeders(): void
    {
        $source = file_get_contents(database_path('seeders/AdminSeeder.php'));

        $this->assertStringNotContainsString("'password' => '", $source);
        $this->assertStringContainsString("config('admin.password')", $source);
    }
}
