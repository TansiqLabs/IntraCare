<?php

declare(strict_types=1);

namespace App\Enums;

enum QueueTicketStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case Served = 'served';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => __('Waiting'),
            self::Called => __('Called'),
            self::Served => __('Served'),
            self::NoShow => __('No-show'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Waiting => 'warning',
            self::Called => 'info',
            self::Served => 'success',
            self::NoShow => 'danger',
        };
    }
}
