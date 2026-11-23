<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canOperateOrders($user, Filament::getTenant());
    }

    public function view(User $user, Order $order): bool
    {
        return $order->restaurant_id === Filament::getTenant()?->getKey()
            && $this->canOperateOrders($user, $order->restaurant);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }

    private function canOperateOrders(User $user, mixed $restaurant): bool
    {
        if (! $restaurant instanceof Restaurant) {
            return false;
        }

        return $user->restaurants()
            ->whereKey($restaurant->getKey())
            ->wherePivotIn('role', ['owner', 'manager', 'attendant', 'kitchen'])
            ->exists();
    }
}
