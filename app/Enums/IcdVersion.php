<?php

declare(strict_types=1);

namespace App\Enums;

enum IcdVersion: string
{
    case Icd10 = 'icd10';
    case Icd11 = 'icd11';

    public function label(): string
    {
        return match ($this) {
            self::Icd10 => __('ICD-10'),
            self::Icd11 => __('ICD-11'),
        };
    }
}
