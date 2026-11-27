<?php

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\User;

class RestaurantPolicy
{
    public function view(User $user, ?Restaurant $restaurant = null): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ?Restaurant $restaurant = null): bool
    {
        return $user->isAdmin();
    }
}
