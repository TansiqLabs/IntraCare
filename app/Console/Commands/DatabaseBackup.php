<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Automated PostgreSQL database backup to local filesystem.
 *
 * Generates a timestamped .sql.gz dump file at the configured backup path.
 * Designed for cron scheduling (daily recommended).
 *
 * Usage:
 *   php artisan db:backup              # Run once
 *   Schedule: daily at 02:00 AM        # See routes/console.php
 */
class DatabaseBackup extends Command
{
    protected $signature = 'db:backup
                            {--keep=30 : Number of days to keep old backups}';

    protected $description = 'Create a compressed PostgreSQL database backup to local storage';

    public function handle(): int
    {
        $this->info('Starting database backup...');

        $disk = Storage::disk('backups');
        $date = Carbon::now();

        // Organize by year/month: database/2026/February/
        $directory = sprintf(
            'database/%s/%s',
            $date->format('Y'),
            $date->format('F')
        );

        $filename = sprintf(
            'intracare_%s.sql.gz',
            $date->format('Y-m-d_H-i-s')
        );

        $fullPath = $directory . '/' . $filename;

        // Ensure directory exists
        $disk->makeDirectory($directory);

        $absolutePath = rtrim($disk->path(''), '/') . '/' . $fullPath;

        // Build pg_dump command
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -Fc --no-owner --no-acl %s | gzip > %s',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($absolutePath)
        );

        $result = Process::timeout(300)->run($command);

        if ($result->failed()) {
            $this->error('Backup failed: ' . $result->errorOutput());

            return self::FAILURE;
        }

        $sizeKb = round(filesize($absolutePath) / 1024, 1);
        $this->info("Backup created: {$fullPath} ({$sizeKb} KB)");

        // Cleanup old backups
        $this->cleanupOldBackups($disk, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    protected function cleanupOldBackups(\Illuminate\Filesystem\FilesystemAdapter $disk, int $keepDays): void
    {
        $cutoff = Carbon::now()->subDays($keepDays)->timestamp;
        $deleted = 0;

        foreach ($disk->allFiles('database') as $file) {
            if (str_ends_with($file, '.sql.gz') && $disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} backup(s) older than {$keepDays} days.");
        }
    }
}
