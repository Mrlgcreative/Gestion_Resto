<?php

namespace Shared\Enums;

enum KitchenStatus: string
{
    case Waiting = 'waiting';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'En attente',
            self::Preparing => 'En préparation',
            self::Ready => 'Prêt',
            self::Served => 'Servi',
        };
    }
}
