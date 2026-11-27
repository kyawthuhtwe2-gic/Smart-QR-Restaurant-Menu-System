<?php

/*
|--------------------------------------------------------------------------
| Initial administrator (used by AdminSeeder only)
|--------------------------------------------------------------------------
| No default password exists. Set ADMIN_PASSWORD in .env before seeding.
*/
return [
    'name' => env('ADMIN_NAME', 'Administrator'),
    'email' => env('ADMIN_EMAIL', 'admin@example.com'),
    'password' => env('ADMIN_PASSWORD'),
];
