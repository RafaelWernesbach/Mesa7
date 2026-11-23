<?php

namespace App\Actions\Orders;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    /**
     * @param  array<int|string, mixed>  $cartItems  Untrusted product ID to quantity values.
     */
    public function execute(
        Restaurant $restaurant,
        RestaurantTable $table,
        array $cartItems,
        ?string $notes = null,
    ): Order {
        if ($cartItems === []) {
            throw ValidationException::withMessages([
                'cart' => 'Adicione pelo menos um produto antes de confirmar.',
            ]);
        }

        return DB::transaction(function () use ($restaurant, $table, $cartItems, $notes): Order {
            $lockedRestaurant = Restaurant::query()
                ->whereKey($restaurant->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedTable = $lockedRestaurant->tables()
                ->whereKey($table->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedRestaurant->is_active || ! $lockedTable?->is_active) {
                throw ValidationException::withMessages([
                    'table' => 'Esta mesa não está disponível para receber pedidos.',
                ]);
            }

            $quantities = [];

            foreach ($cartItems as $productId => $quantity) {
                if (! ctype_digit((string) $productId) || ! is_int($quantity) || $quantity < 1 || $quantity > 99) {
                    throw ValidationException::withMessages([
                        'cart' => 'O carrinho contém uma quantidade inválida. Revise os produtos e tente novamente.',
                    ]);
                }

                $quantities[(int) $productId] = $quantity;
            }

            $products = $lockedRestaurant->products()
                ->whereIn('id', array_keys($quantities))
                ->where('is_available', true)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($product): int => (int) $product->getKey());

            if ($products->count() !== count($quantities)) {
                throw ValidationException::withMessages([
                    'cart' => 'Um produto do carrinho ficou indisponível. Revise o pedido antes de confirmar.',
                ]);
            }

            $subtotalCents = 0;
            foreach ($quantities as $productId => $quantity) {
                $subtotalCents += Money::toCents((string) $products->get($productId)->price) * $quantity;
            }

            if ($subtotalCents > 9_999_999_999) {
                throw ValidationException::withMessages([
                    'cart' => 'O valor total excede o limite permitido para um pedido.',
                ]);
            }

            $now = now();
            $subtotal = Money::fromCents($subtotalCents);
            $order = $lockedRestaurant->orders()->create([
                'restaurant_table_id' => $lockedTable->getKey(),
                'public_id' => 'M7-'.Str::upper((string) Str::uuid()),
                'source' => OrderSource::Table,
                'status' => OrderStatus::New,
                'notes' => $notes,
                'subtotal' => $subtotal,
                'delivery_fee' => '0.00',
                'total' => $subtotal,
                'placed_at' => $now,
            ]);

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                $unitPrice = (string) $product->price;
                $order->items()->create([
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => Money::fromCents(Money::toCents($unitPrice) * $quantity),
                ]);
            }

            $order->statusHistories()->create([
                'status' => OrderStatus::New,
                'changed_at' => $now,
            ]);

            return $order->load(['restaurantTable', 'items', 'statusHistories']);
        });
    }
}
