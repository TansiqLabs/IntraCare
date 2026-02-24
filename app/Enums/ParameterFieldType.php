<?php

declare(strict_types=1);

namespace App\Enums;

enum ParameterFieldType: string
{
    case Numeric = 'numeric';
    case Text = 'text';
    case Select = 'select';
    case Boolean = 'boolean';

    public function label(): string
    {
        return match ($this) {
            self::Numeric => __('Numeric'),
            self::Text => __('Text'),
            self::Select => __('Select/Dropdown'),
            self::Boolean => __('Yes/No'),
        };
    }
}
