<?php

namespace App\Support;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;

class PublicTableCart
{
    private const SESSION_KEY = 'mesa7.public_table_cart';

    /** @return array<int, int> Product ID to quantity. */
    public function items(Request $request, Restaurant $restaurant, RestaurantTable $table): array
    {
        $cart = $request->session()->get(self::SESSION_KEY);

        if (! is_array($cart)
            || ($cart['restaurant_id'] ?? null) !== $restaurant->getKey()
            || ($cart['table_id'] ?? null) !== $table->getKey()
            || ! is_array($cart['items'] ?? null)) {
            return [];
        }

        $items = [];
        foreach ($cart['items'] as $productId => $quantity) {
            if (ctype_digit((string) $productId)
                && is_int($quantity)
                && $quantity > 0
                && $quantity <= 99) {
                $items[(int) $productId] = $quantity;
            }
        }

        return $items;
    }

    /** @param array<int, int> $items Product ID to quantity. */
    public function save(Request $request, Restaurant $restaurant, RestaurantTable $table, array $items): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'restaurant_id' => $restaurant->getKey(),
            'table_id' => $table->getKey(),
            'items' => $items,
        ]);
    }

    public function count(Request $request, Restaurant $restaurant, RestaurantTable $table): int
    {
        return array_sum($this->items($request, $restaurant, $table));
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
