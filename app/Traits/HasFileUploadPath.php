<?php

declare(strict_types=1);

namespace App\Traits;

use App\Helpers\FileUploadPath;

/**
 * Convenience trait for models/services that want a consistent upload directory.
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
