<?php

use App\Actions\Orders\TransitionOrderStatus;
use App\Enums\OrderStatus;

it('allows only the defined forward and cancellation transitions', function (
    OrderStatus $current,
    array $expected,
) {
    expect(TransitionOrderStatus::allowedTransitions($current))->toBe($expected);
})->with([
    'new orders can start or be cancelled' => [OrderStatus::New, [OrderStatus::Preparing, OrderStatus::Cancelled]],
    'preparing orders can become ready or be cancelled' => [OrderStatus::Preparing, [OrderStatus::Ready, OrderStatus::Cancelled]],
    'ready orders can finish or be cancelled' => [OrderStatus::Ready, [OrderStatus::Completed, OrderStatus::Cancelled]],
    'completed orders are terminal' => [OrderStatus::Completed, []],
    'cancelled orders are terminal' => [OrderStatus::Cancelled, []],
]);
