<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Receive = 'receive';
    case Dispense = 'dispense';
    case Adjust = 'adjust';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Receive => __('Receive'),
            self::Dispense => __('Dispense'),
            self::Adjust => __('Adjust'),
            self::Return => __('Return'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Receive => 'success',
            self::Dispense => 'warning',
            self::Adjust => 'info',
            self::Return => 'danger',
        };
    }
}
