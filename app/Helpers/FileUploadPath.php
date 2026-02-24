<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Carbon;

/**
 * Generates WordPress-style year/month upload paths.
 *
 * Example: uploads/2026/February/filename.jpg
 *
 * Usage in Filament FileUpload:
 *   FileUpload::make('photo_path')
 *       ->directory(FileUploadPath::generate('patients'))
 *
 * Or in a Model:
 *   use HasFileUploadPath;
 *   $path = $this->uploadPath('patients');
 */
trait HasFileUploadPath
{
    /**
     * Generate a year/month upload directory path.
     *
     * @param  string  $prefix  Subfolder prefix (e.g., 'patients', 'lab-reports')
     */
    public function uploadPath(string $prefix = 'uploads'): string
    {
        return FileUploadPath::generate($prefix);
    }
}

/**
 * Static helper for generating year/month upload paths.
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
