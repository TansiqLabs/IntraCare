<?php

declare(strict_types=1);

namespace App\Enums;

enum LabOrderTestStatus: string
{
    case Pending = 'pending';
    case SampleCollected = 'sample_collected';
    case Processing = 'processing';
    case ResultEntered = 'result_entered';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::SampleCollected => __('Sample Collected'),
            self::Processing => __('Processing'),
            self::ResultEntered => __('Result Entered'),
            self::Verified => __('Verified'),
            self::Rejected => __('Rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::SampleCollected => 'info',
            self::Processing => 'primary',
            self::ResultEntered => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }
}
