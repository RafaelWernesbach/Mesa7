<?php

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\User;

class RestaurantPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_super_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_super_admin;
    }

    public function update(User $user, Restaurant $restaurant): bool
    {
        return (bool) $user->is_super_admin;
    }
}
