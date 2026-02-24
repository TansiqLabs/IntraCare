<?php

declare(strict_types=1);

namespace App\Enums;

enum VisitType: string
{
    case Opd = 'opd';
    case FollowUp = 'follow_up';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::Opd => __('OPD'),
            self::FollowUp => __('Follow Up'),
            self::Emergency => __('Emergency'),
        };
    }
}
