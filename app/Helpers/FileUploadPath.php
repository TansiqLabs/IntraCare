<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Carbon;

/**
 * Static helper for generating WordPress-style year/month upload paths.
 *
 * Example: uploads/2026/February/
 */
class FileUploadPath
{
    /**
     * Generate: {prefix}/{year}/{month}/
     *
     * Examples:
     *  - patients/2026/February/
     *  - lab-reports/2026/January/
     *  - documents/2026/March/
     */
    public static function generate(string $prefix = 'uploads', ?Carbon $date = null): string
    {
        $date ??= Carbon::now();

        return sprintf(
            '%s/%s/%s',
            trim($prefix, '/'),
            $date->format('Y'),
            $date->format('F')
        );
    }
}
