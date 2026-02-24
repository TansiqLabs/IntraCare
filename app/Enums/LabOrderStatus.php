<?php

declare(strict_types=1);

namespace App\Enums;

enum LabOrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case SampleCollected = 'sample_collected';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => __('Pending Payment'),
            self::Paid => __('Paid'),
            self::SampleCollected => __('Sample Collected'),
            self::Processing => __('Processing'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Paid => 'info',
            self::SampleCollected => 'info',
            self::Processing => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
