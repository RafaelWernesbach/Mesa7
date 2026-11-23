<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionOrderStatus
{
    public function execute(Order $order, OrderStatus $nextStatus, User $user): Order
    {
        $isOperator = $user->restaurants()
            ->whereKey($order->restaurant_id)
            ->wherePivotIn('role', ['owner', 'manager', 'attendant', 'kitchen'])
            ->exists();

        if (! $isOperator) {
            throw new AuthorizationException('Você não pode operar pedidos deste estabelecimento.');
        }

        return DB::transaction(function () use ($order, $nextStatus, $user): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus = OrderStatus::from((string) $lockedOrder->getRawOriginal('status'));

            if (! in_array($nextStatus, self::allowedTransitions($currentStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => 'Esta mudança de status não é permitida.',
                ]);
            }

            $changedAt = now();
            $lockedOrder->status = $nextStatus->value;
            if ($nextStatus === OrderStatus::Completed) {
                $lockedOrder->completed_at = $changedAt;
            }
            $lockedOrder->save();

            $lockedOrder->statusHistories()->create([
                'changed_by_user_id' => $user->getKey(),
                'status' => $nextStatus,
                'changed_at' => $changedAt,
            ]);

            return $lockedOrder->load(['restaurantTable', 'items', 'statusHistories.changedBy']);
        });
    }

    /** @return list<OrderStatus> */
    public static function allowedTransitions(OrderStatus $current): array
    {
        return match ($current) {
            OrderStatus::New => [OrderStatus::Preparing, OrderStatus::Cancelled],
            OrderStatus::Preparing => [OrderStatus::Ready, OrderStatus::Cancelled],
            OrderStatus::Ready => [OrderStatus::Completed, OrderStatus::Cancelled],
            OrderStatus::Completed, OrderStatus::Cancelled => [],
        };
    }
}
