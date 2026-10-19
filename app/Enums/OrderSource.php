<?php

namespace App\Enums;

enum OrderSource: string
{
    case Table = 'table';
    case Delivery = 'delivery';
    case Pickup = 'pickup';
}
