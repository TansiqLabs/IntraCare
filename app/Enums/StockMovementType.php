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
            self::Receive => 'Receive',
            self::Dispense => 'Dispense',
            self::Adjust => 'Adjust',
            self::Return => 'Return',
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
