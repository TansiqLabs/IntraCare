<?php

declare(strict_types=1);

namespace App\Enums;

enum AllergySeverity: string
{
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';
    case LifeThreatening = 'life_threatening';

    public function label(): string
    {
        return match ($this) {
            self::Mild => __('Mild'),
            self::Moderate => __('Moderate'),
            self::Severe => __('Severe'),
            self::LifeThreatening => __('Life Threatening'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Mild => 'info',
            self::Moderate => 'warning',
            self::Severe => 'danger',
            self::LifeThreatening => 'danger',
        };
    }
}
