<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceType: string
{
    case Lab = 'lab';
    case Opd = 'opd';
    case Pharmacy = 'pharmacy';

    public function label(): string
    {
        return match ($this) {
            self::Lab => __('Lab'),
            self::Opd => __('OPD'),
            self::Pharmacy => __('Pharmacy'),
        };
    }
}
