<?php

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Restaurant;

it('links categories products tables and order items through their domain models', function () {
    $restaurant = Restaurant::factory()->create();
    $category = $restaurant->categories()->create([
        'name' => 'Lanches',
        'slug' => 'lanches',
        'sort_order' => 1,
        'is_active' => true,
    ]);
    $product = $restaurant->products()->create([
        'name' => 'Hambúrguer da Casa',
        'price' => '34.90',
        'is_available' => true,
    ]);
    $product->category()->associate($category);
    $product->save();

    $table = $restaurant->tables()->create([
        'name' => 'Mesa 7',
        'code' => 'M07',
        'is_active' => true,
    ]);
    $order = $restaurant->orders()->create([
        'restaurant_table_id' => $table->id,
        'public_id' => 'M7-8F42K',
        'source' => OrderSource::Table,
        'status' => OrderStatus::New,
        'subtotal' => '34.90',
        'delivery_fee' => '0.00',
        'total' => '34.90',
        'placed_at' => now(),
    ]);
    $item = $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => $product->price,
        'quantity' => 1,
        'line_total' => '34.90',
    ]);

    $product->update(['name' => 'Hambúrguer atualizado']);

    expect($restaurant->categories)->toHaveCount(1)
        ->and($restaurant->tables)->toHaveCount(1)
        ->and($restaurant->orders)->toHaveCount(1)
        ->and($product->fresh()->category->is($category))->toBeTrue()
        ->and($order->fresh()->restaurantTable->is($table))->toBeTrue()
        ->and($order->fresh()->items->first()->is($item))->toBeTrue()
        ->and($order->fresh()->source)->toBe(OrderSource::Table)
        ->and($order->fresh()->status)->toBe(OrderStatus::New)
        ->and($order->fresh()->total)->toBe('34.90')
        ->and($item->fresh()->product_name)->toBe('Hambúrguer da Casa')
        ->and($item->fresh()->unit_price)->toBe('34.90');
});

it('links optional restaurant domains to their establishment', function () {
    $restaurant = Restaurant::factory()->create();
    $domain = $restaurant->domains()->create(['domain' => 'cantina.example.test']);

    expect($restaurant->domains)->toHaveCount(1)
        ->and($domain->restaurant->is($restaurant))->toBeTrue();
});
