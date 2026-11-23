<?php

namespace App\Enums;

enum OrderSource: string
{
    case Table = 'table';
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Table => 'Mesa',
            self::Delivery => 'Delivery',
            self::Pickup => 'Retirada',
        };
    }
}
