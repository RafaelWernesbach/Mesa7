<?php

use App\Actions\Orders\CreateOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;

function publicOrderRoute(string $name, Restaurant $restaurant, RestaurantTable $table, array $parameters = []): string
{
    return route($name, array_merge([
        'restaurant' => $restaurant->slug,
        'uuid' => $table->uuid,
    ], $parameters));
}

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['slug' => 'casa-do-teste']);
    $this->table = $this->restaurant->tables()->create([
        'name' => 'Mesa 07',
        'code' => 'M07',
        'is_active' => true,
    ]);
});

it('lets a customer add, update and remove products in a table-specific cart', function () {
    $product = $this->restaurant->products()->create([
        'name' => 'Pão de queijo',
        'price' => '8.50',
        'is_available' => true,
    ]);

    $this->post(publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table), [
        'product_id' => $product->getKey(),
        'quantity' => 2,
    ])->assertRedirect(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table));

    $this->get(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->assertOk()
        ->assertSee('Pão de queijo')
        ->assertSee('R$ 17,00');

    $this->patch(publicOrderRoute('public.orders.cart.items.update', $this->restaurant, $this->table, [
        'product' => $product->getKey(),
    ]), ['quantity' => 3])->assertRedirect();

    $this->get(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->assertOk()
        ->assertSee('R$ 25,50');

    $this->delete(publicOrderRoute('public.orders.cart.items.destroy', $this->restaurant, $this->table, [
        'product' => $product->getKey(),
    ]))->assertRedirect();

    $this->get(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->assertOk()
        ->assertSee('Seu carrinho está vazio.');
});

it('creates a server-priced order with product snapshots and public status tracking', function () {
    $firstProduct = $this->restaurant->products()->create([
        'name' => 'Café coado',
        'price' => '6.50',
        'is_available' => true,
    ]);
    $secondProduct = $this->restaurant->products()->create([
        'name' => 'Bolo de milho',
        'price' => '9.90',
        'is_available' => true,
    ]);

    $this->post(publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table), [
        'product_id' => $firstProduct->getKey(),
        'quantity' => 2,
    ]);
    $this->post(publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table), [
        'product_id' => $secondProduct->getKey(),
        'quantity' => 1,
        'unit_price' => '0.01',
        'total' => '0.01',
    ]);

    $response = $this->post(publicOrderRoute('public.orders.store', $this->restaurant, $this->table), [
        'notes' => 'Sem açúcar, por favor.',
    ]);

    $order = Order::query()->with('items')->firstOrFail();
    $this->assertDatabaseHas(Order::class, [
        'id' => $order->getKey(),
        'restaurant_id' => $this->restaurant->getKey(),
        'restaurant_table_id' => $this->table->getKey(),
        'source' => 'table',
        'status' => 'new',
        'subtotal' => '22.90',
        'total' => '22.90',
        'notes' => 'Sem açúcar, por favor.',
    ]);
    expect($order->public_id)->toStartWith('M7-')
        ->and($order->items)->toHaveCount(2)
        ->and($order->statusHistories()->count())->toBe(1)
        ->and($order->statusHistories()->first()->status)->toBe(OrderStatus::New);

    $response->assertRedirect(route('public.orders.track', [
        'restaurant' => $this->restaurant->slug,
        'publicId' => $order->public_id,
    ]));

    $firstProduct->update(['name' => 'Café alterado', 'price' => '99.00']);
    $firstProduct->delete();

    $itemSnapshot = OrderItem::query()->where('product_name', 'Café coado')->firstOrFail();
    expect($itemSnapshot->product_id)->toBeNull()
        ->and($itemSnapshot->unit_price)->toBe('6.50')
        ->and($itemSnapshot->line_total)->toBe('13.00');

    $this->get(route('public.orders.track', [
        'restaurant' => $this->restaurant->slug,
        'publicId' => $order->public_id,
    ]))
        ->assertOk()
        ->assertSee($order->public_id)
        ->assertSee('Café coado')
        ->assertSee('R$ 22,90')
        ->assertSee('Pendente');

    $this->get(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->assertOk()
        ->assertSee('Seu carrinho está vazio.');
});

it('rejects products from another tenant, unavailable products and empty orders', function () {
    $otherRestaurant = Restaurant::factory()->create();
    $foreignProduct = $otherRestaurant->products()->create([
        'name' => 'Produto de outra casa',
        'price' => '10.00',
        'is_available' => true,
    ]);
    $unavailableProduct = $this->restaurant->products()->create([
        'name' => 'Produto indisponível',
        'price' => '10.00',
        'is_available' => false,
    ]);

    foreach ([$foreignProduct, $unavailableProduct] as $product) {
        $this->post(publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table), [
            'product_id' => $product->getKey(),
        ])->assertNotFound();
    }

    $this->from(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->post(publicOrderRoute('public.orders.store', $this->restaurant, $this->table))
        ->assertRedirect(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->assertSessionHasErrors('cart');

    expect(Order::query()->count())->toBe(0);
});

it('keeps cart context on the selected table and enforces the per-product quantity limit', function () {
    $product = $this->restaurant->products()->create([
        'name' => 'Água mineral',
        'price' => '4.00',
        'is_available' => true,
    ]);
    $otherTable = $this->restaurant->tables()->create([
        'name' => 'Mesa 08',
        'code' => 'M08',
        'is_active' => true,
    ]);

    $this->post(publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table), [
        'product_id' => $product->getKey(),
        'quantity' => 99,
    ]);

    $this->from(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->post(publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table), [
            'product_id' => $product->getKey(),
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('quantity');

    $this->get(publicOrderRoute('public.orders.cart', $this->restaurant, $otherTable))
        ->assertOk()
        ->assertSee('Seu carrinho está vazio.');
});

it('does not accept an order after a table or its restaurant is deactivated', function () {
    $product = $this->restaurant->products()->create([
        'name' => 'Suco natural',
        'price' => '12.00',
        'is_available' => true,
    ]);

    $this->post(publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table), [
        'product_id' => $product->getKey(),
    ]);
    $this->table->update(['is_active' => false]);

    $this->get(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))->assertNotFound();
    expect(Order::query()->count())->toBe(0);

    $this->table->update(['is_active' => true]);
    $this->restaurant->update(['is_active' => false]);

    $this->post(publicOrderRoute('public.orders.store', $this->restaurant, $this->table))
        ->assertNotFound();
    expect(Order::query()->count())->toBe(0);
});

it('rejects unavailable cart items at confirmation and isolates public tracking by tenant', function () {
    $product = $this->restaurant->products()->create([
        'name' => 'Chá gelado',
        'price' => '7.00',
        'is_available' => true,
    ]);

    $this->post(publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table), [
        'product_id' => $product->getKey(),
    ]);
    $product->update(['is_available' => false]);

    $this->get(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->assertOk()
        ->assertSee('Este produto ficou indisponível.');

    $this->from(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->post(publicOrderRoute('public.orders.store', $this->restaurant, $this->table))
        ->assertSessionHasErrors('cart');

    expect(Order::query()->count())->toBe(0);

    $availableProduct = $this->restaurant->products()->create([
        'name' => 'Café espresso',
        'price' => '5.50',
        'is_available' => true,
    ]);
    $validOrder = app(CreateOrder::class)->execute(
        $this->restaurant,
        $this->table,
        [$availableProduct->getKey() => 1],
    );
    $otherRestaurant = Restaurant::factory()->create();
    $this->get(route('public.orders.track', [
        'restaurant' => $otherRestaurant->slug,
        'publicId' => $validOrder->public_id,
    ]))->assertNotFound();
});
