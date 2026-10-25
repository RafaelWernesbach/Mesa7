<?php

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Filament\Facades\Filament;

class RestaurantTablePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageTables($user, Filament::getTenant());
    }

    public function view(User $user, RestaurantTable $table): bool
    {
        return $table->restaurant_id === Filament::getTenant()?->getKey()
            && $this->canManageTables($user, $table->restaurant);
    }

    public function create(User $user): bool
    {
        return $this->canManageTables($user, Filament::getTenant());
    }

    public function update(User $user, RestaurantTable $table): bool
    {
        return $this->view($user, $table);
    }

    public function delete(User $user, RestaurantTable $table): bool
    {
        return $this->view($user, $table);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageTables($user, Filament::getTenant());
    }

    private function canManageTables(User $user, mixed $restaurant): bool
    {
        if (! $restaurant instanceof Restaurant) {
            return false;
        }

        return $user->restaurants()
            ->whereKey($restaurant->getKey())
            ->wherePivotIn('role', ['owner', 'manager'])
            ->exists();
    }
}
