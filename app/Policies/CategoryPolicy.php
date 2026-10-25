<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageCatalog($user, Filament::getTenant());
    }

    public function view(User $user, Category $category): bool
    {
        return $category->restaurant_id === Filament::getTenant()?->getKey()
            && $this->canManageCatalog($user, $category->restaurant);
    }

    public function create(User $user): bool
    {
        return $this->canManageCatalog($user, Filament::getTenant());
    }

    public function update(User $user, Category $category): bool
    {
        return $this->view($user, $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->view($user, $category);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageCatalog($user, Filament::getTenant());
    }

    private function canManageCatalog(User $user, mixed $restaurant): bool
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
