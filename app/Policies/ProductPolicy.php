<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;

class ProductPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageCatalog($user, Filament::getTenant());
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageCatalog($user, Filament::getTenant());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        return $product->restaurant_id === Filament::getTenant()?->getKey()
            && $this->canManageCatalog($user, $product->restaurant);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManageCatalog($user, Filament::getTenant());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        return $product->restaurant_id === Filament::getTenant()?->getKey()
            && $this->canManageCatalog($user, $product->restaurant);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return $product->restaurant_id === Filament::getTenant()?->getKey()
            && $this->canManageCatalog($user, $product->restaurant);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
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
