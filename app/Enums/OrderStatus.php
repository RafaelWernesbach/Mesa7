<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Pendente',
            self::Preparing => 'Em preparo',
            self::Ready => 'Pronto',
            self::Completed => 'Finalizado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Preparing => 'info',
            self::Ready => 'success',
            self::Completed => 'gray',
            self::Cancelled => 'danger',
        };
    }
}
