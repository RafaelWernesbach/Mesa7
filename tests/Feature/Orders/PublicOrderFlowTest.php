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

it('returns current table orders for polling without exposing other tables', function () {
    $product = $this->restaurant->products()->create(['name' => 'Café', 'price' => '5.00', 'is_available' => true]);
    $otherTable = $this->restaurant->tables()->create(['name' => 'Mesa 08', 'code' => 'M08', 'is_active' => true]);
    $order = app(CreateOrder::class)->execute($this->restaurant, $this->table, [$product->id => 1]);
    $other = app(CreateOrder::class)->execute($this->restaurant, $otherTable, [$product->id => 1]);
    $order->update(['status' => OrderStatus::Preparing]);

    $this->getJson(publicOrderRoute('public.orders.index', $this->restaurant, $this->table))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonCount(1, 'orders')
        ->assertJsonPath('orders.0.id', $order->public_id)
        ->assertJsonPath('orders.0.status', 'Em preparo')
        ->assertJsonPath('orders.0.url', route('public.orders.track', [$this->restaurant->slug, $order->public_id]))
        ->assertDontSee($other->public_id);

    $otherRestaurant = Restaurant::factory()->create();
    $this->getJson(publicOrderRoute('public.orders.index', $otherRestaurant, $this->table))->assertNotFound();
    $this->table->update(['is_active' => false]);
    $this->getJson(publicOrderRoute('public.orders.index', $this->restaurant, $this->table))->assertNotFound();
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
    ])->assertRedirect(route('public.menu.table', [$this->restaurant->slug, $this->table->uuid]));

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

it('adds items through JSON and returns the updated cart count', function () {
    $product = $this->restaurant->products()->create(['name' => 'Café', 'price' => '5.00', 'is_available' => true]);
    $url = publicOrderRoute('public.orders.cart.items.store', $this->restaurant, $this->table);

    $this->postJson($url, ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk()->assertJsonPath('count', 2);
    $this->postJson($url, ['product_id' => $product->id, 'quantity' => 1])
        ->assertOk()->assertJsonPath('count', 3);
    $this->get(publicOrderRoute('public.orders.cart', $this->restaurant, $this->table))
        ->assertSee('R$ 15,00');
    $this->postJson($url, ['product_id' => $product->id, 'quantity' => 99])
        ->assertUnprocessable()->assertJsonValidationErrors('quantity');
});

it('checks a browser cart against current prices and availability before creating the order', function () {
    $product = $this->restaurant->products()->create(['name' => 'Café', 'price' => '5.00', 'is_available' => true]);
    $url = publicOrderRoute('public.orders.store', $this->restaurant, $this->table);
    $items = [['product_id' => $product->id, 'quantity' => 2, 'price_cents' => 500]];

    $product->update(['price' => '6.00']);
    $this->postJson($url, ['items' => $items])->assertUnprocessable()->assertJsonValidationErrors('cart');
    expect(Order::query()->count())->toBe(0);

    $items[0]['price_cents'] = 600;
    $product->update(['is_available' => false]);
    $this->postJson($url, ['items' => $items])->assertUnprocessable()->assertJsonValidationErrors('cart');
    expect(Order::query()->count())->toBe(0);

    $product->update(['is_available' => true]);
    $response = $this->postJson($url, ['items' => $items, 'total' => '0.01']);
    $order = Order::query()->firstOrFail();
    $response->assertOk()->assertJsonPath('url', route('public.orders.track', [$this->restaurant->slug, $order->public_id]));
    expect($order->total)->toBe('12.00');
});

it('rejects invalid browser carts and products from another restaurant', function () {
    $other = Restaurant::factory()->create();
    $product = $other->products()->create(['name' => 'Chá', 'price' => '5.00', 'is_available' => true]);
    $url = publicOrderRoute('public.orders.store', $this->restaurant, $this->table);
    $item = ['product_id' => $product->id, 'quantity' => 1, 'price_cents' => 500];
    $this->postJson($url, ['items' => [$item]])->assertUnprocessable()->assertJsonValidationErrors('cart');
    $this->postJson($url, ['items' => [$item, $item]])->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');
    $this->postJson($url, ['items' => []])->assertUnprocessable()->assertJsonValidationErrors('items');
    expect(Order::query()->count())->toBe(0);
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
