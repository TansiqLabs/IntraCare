<?php

declare(strict_types=1);

namespace App\Enums;

enum SampleStatus: string
{
    case Collected = 'collected';
    case ReceivedInLab = 'received_in_lab';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Collected => __('Collected'),
            self::ReceivedInLab => __('Received in Lab'),
            self::Rejected => __('Rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Collected => 'info',
            self::ReceivedInLab => 'success',
            self::Rejected => 'danger',
        };
    }
}
