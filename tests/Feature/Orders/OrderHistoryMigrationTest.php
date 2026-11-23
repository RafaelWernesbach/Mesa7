<?php

use App\Actions\Orders\CreateOrder;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;

it('backfills the current status of existing orders when history is introduced', function () {
    $restaurant = Restaurant::factory()->create();
    $table = $restaurant->tables()->create(['name' => 'Mesa antiga', 'code' => 'M01']);
    $product = $restaurant->products()->create([
        'name' => 'Prato antigo',
        'price' => '20.00',
        'is_available' => true,
    ]);
    $order = app(CreateOrder::class)->execute($restaurant, $table, [$product->getKey() => 1]);

    DB::table('orders')->where('id', $order->getKey())->update(['status' => 'preparing']);

    $migration = require database_path('migrations/2026_10_19_030080_create_order_status_histories_table.php');
    $migration->down();
    $migration->up();

    $history = DB::table('order_status_histories')->where('order_id', $order->getKey())->first();

    expect($history)->not->toBeNull()
        ->and($history->status)->toBe('preparing')
        ->and($history->changed_by_user_id)->toBeNull()
        ->and($history->changed_at)->toBe($order->placed_at->toDateTimeString());
});
