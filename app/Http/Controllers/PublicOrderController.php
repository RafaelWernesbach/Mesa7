<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CreateOrder;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Support\Money;
use App\Support\PublicTableCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicOrderController extends Controller
{
    public function showCart(
        Request $request,
        Restaurant $restaurant,
        string $uuid,
        PublicTableCart $cart,
    ): View {
        $table = $this->activeTable($restaurant, $uuid);
        $quantities = $cart->items($request, $restaurant, $table);
        $products = $restaurant->products()->whereIn('id', array_keys($quantities))->get()->keyBy('id');
        $quantities = array_intersect_key($quantities, $products->all());
        $cart->save($request, $restaurant, $table, $quantities);
        $cartItems = [];
        $subtotalCents = 0;

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);
            if ($product === null) {
                continue;
            }

            $unitPrice = (string) $product->price;
            $lineTotal = Money::fromCents(Money::toCents($unitPrice) * $quantity);
            $subtotalCents += Money::toCents($lineTotal);
            $cartItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'lineTotal' => $lineTotal,
                'available' => $product->is_available,
            ];
        }

        $canConfirm = $cartItems !== [] && collect($cartItems)->every(fn (array $item): bool => $item['available']);

        return view('public.cart', [
            'restaurant' => $restaurant,
            'table' => $table,
            'cartItems' => $cartItems,
            'subtotal' => Money::fromCents($subtotalCents),
            'canConfirm' => $canConfirm,
        ]);
    }

    public function addItem(
        Request $request,
        Restaurant $restaurant,
        string $uuid,
        PublicTableCart $cart,
    ): RedirectResponse {
        $table = $this->activeTable($restaurant, $uuid);
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);
        $productId = (int) $data['product_id'];

        abort_unless(
            $restaurant->products()->whereKey($productId)->where('is_available', true)->exists(),
            404,
        );

        $items = $cart->items($request, $restaurant, $table);
        $quantity = ($items[$productId] ?? 0) + (int) ($data['quantity'] ?? 1);
        if ($quantity > 99) {
            throw ValidationException::withMessages([
                'quantity' => 'O limite por produto é 99 unidades.',
            ]);
        }

        $items[$productId] = $quantity;
        $cart->save($request, $restaurant, $table, $items);

        return redirect()->route('public.orders.cart', [
            'restaurant' => $restaurant->slug,
            'uuid' => $table->uuid,
        ])->with('status', 'Produto adicionado ao carrinho.');
    }

    public function updateItem(
        Request $request,
        Restaurant $restaurant,
        string $uuid,
        int $product,
        PublicTableCart $cart,
    ): RedirectResponse {
        $table = $this->activeTable($restaurant, $uuid);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
        $items = $cart->items($request, $restaurant, $table);
        abort_unless(array_key_exists($product, $items), 404);

        $items[$product] = (int) $data['quantity'];
        $cart->save($request, $restaurant, $table, $items);

        return back()->with('status', 'Quantidade atualizada.');
    }

    public function removeItem(
        Request $request,
        Restaurant $restaurant,
        string $uuid,
        int $product,
        PublicTableCart $cart,
    ): RedirectResponse {
        $table = $this->activeTable($restaurant, $uuid);
        $items = $cart->items($request, $restaurant, $table);
        abort_unless(array_key_exists($product, $items), 404);

        unset($items[$product]);
        $cart->save($request, $restaurant, $table, $items);

        return back()->with('status', 'Produto removido do carrinho.');
    }

    public function placeOrder(
        Request $request,
        Restaurant $restaurant,
        string $uuid,
        PublicTableCart $cart,
        CreateOrder $createOrder,
    ): RedirectResponse {
        $table = $this->activeTable($restaurant, $uuid);
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $order = $createOrder->execute(
            $restaurant,
            $table,
            $cart->items($request, $restaurant, $table),
            $data['notes'] ?? null,
        );
        $cart->clear($request);

        return redirect()->route('public.orders.track', [
            'restaurant' => $restaurant->slug,
            'publicId' => $order->public_id,
        ])->with('order_placed', true);
    }

    public function track(Restaurant $restaurant, string $publicId): View
    {
        abort_unless($restaurant->is_active, 404);

        $order = $restaurant->orders()
            ->where('public_id', $publicId)
            ->with(['restaurantTable', 'items', 'statusHistories'])
            ->firstOrFail();

        return view('public.order-tracking', compact('restaurant', 'order'));
    }

    private function activeTable(Restaurant $restaurant, string $uuid): RestaurantTable
    {
        abort_unless($restaurant->is_active, 404);

        return $restaurant->tables()
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->firstOrFail();
    }
}
