<?php

declare(strict_types=1);

namespace App\Enums;

enum DiagnosisType: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Differential = 'differential';

    public function label(): string
    {
        return match ($this) {
            self::Primary => __('Primary'),
            self::Secondary => __('Secondary'),
            self::Differential => __('Differential'),
        };
    }
}
