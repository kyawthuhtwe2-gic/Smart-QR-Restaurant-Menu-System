<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Creates the initial admin once. An existing admin (and its password) is never modified.
     */
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Admin [{$email}] already exists; left unchanged.");

            return;
        }

        if (blank($password)) {
            $this->command?->warn('ADMIN_PASSWORD is not set in .env; admin user was NOT created.');

            return;
        }

        $user = new User([
            'name' => config('admin.name'),
            'email' => $email,
            'password' => $password,
        ]);
        $user->role = User::ROLE_ADMIN;
        $user->email_verified_at = now();
        $user->save();

        $this->command?->info("Admin [{$email}] created.");
    }
}
