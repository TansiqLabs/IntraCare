<?php

declare(strict_types=1);

namespace App\Enums;

enum LabPriority: string
{
    case Routine = 'routine';
    case Urgent = 'urgent';
    case Stat = 'stat';

    public function label(): string
    {
        return match ($this) {
            self::Routine => __('Routine'),
            self::Urgent => __('Urgent'),
            self::Stat => __('STAT'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Routine => 'info',
            self::Urgent => 'warning',
            self::Stat => 'danger',
        };
    }
}
