<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Automated database backup to local filesystem.
 *
 * Supports PostgreSQL, MySQL/MariaDB, and SQLite drivers.
 * Generates a timestamped compressed dump file at the configured backup path.
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

    protected $description = 'Create a compressed database backup to local storage';

    public function handle(): int
    {
        $this->info('Starting database backup...');

        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        if (! $connection) {
            $this->error("No database connection configured for driver: {$driver}");

            return self::FAILURE;
        }

        $disk = Storage::disk('backups');
        $date = Carbon::now();

        // Organize by year/month: database/2026/February/
        $directory = sprintf(
            'database/%s/%s',
            $date->format('Y'),
            $date->format('F')
        );

        $disk->makeDirectory($directory);

        return match ($driver) {
            'pgsql' => $this->backupPostgres($connection, $disk, $directory, $date),
            'mysql', 'mariadb' => $this->backupMysql($connection, $disk, $directory, $date),
            'sqlite' => $this->backupSqlite($connection, $disk, $directory, $date),
            default => $this->unsupportedDriver($driver),
        };
    }

    protected function backupPostgres(array $conn, \Illuminate\Filesystem\FilesystemAdapter $disk, string $directory, Carbon $date): int
    {
        $filename = sprintf('intracare_%s.sql.gz', $date->format('Y-m-d_H-i-s'));
        $absolutePath = rtrim($disk->path(''), '/') . '/' . $directory . '/' . $filename;

        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s --no-owner --no-acl %s | gzip > %s',
            escapeshellarg($conn['password'] ?? ''),
            escapeshellarg($conn['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($conn['port'] ?? '5432')),
            escapeshellarg($conn['username'] ?? ''),
            escapeshellarg($conn['database'] ?? ''),
            escapeshellarg($absolutePath)
        );

        return $this->runBackupCommand($command, $disk, $directory . '/' . $filename, $absolutePath);
    }

    protected function backupMysql(array $conn, \Illuminate\Filesystem\FilesystemAdapter $disk, string $directory, Carbon $date): int
    {
        $filename = sprintf('intracare_%s.sql.gz', $date->format('Y-m-d_H-i-s'));
        $absolutePath = rtrim($disk->path(''), '/') . '/' . $directory . '/' . $filename;

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s --password=%s --single-transaction %s | gzip > %s',
            escapeshellarg($conn['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($conn['port'] ?? '3306')),
            escapeshellarg($conn['username'] ?? ''),
            escapeshellarg($conn['password'] ?? ''),
            escapeshellarg($conn['database'] ?? ''),
            escapeshellarg($absolutePath)
        );

        return $this->runBackupCommand($command, $disk, $directory . '/' . $filename, $absolutePath);
    }

    protected function backupSqlite(array $conn, \Illuminate\Filesystem\FilesystemAdapter $disk, string $directory, Carbon $date): int
    {
        $dbPath = $conn['database'] ?? database_path('database.sqlite');

        if (! file_exists($dbPath)) {
            $this->error("SQLite database file not found: {$dbPath}");

            return self::FAILURE;
        }

        $filename = sprintf('intracare_%s.sqlite.gz', $date->format('Y-m-d_H-i-s'));
        $absolutePath = rtrim($disk->path(''), '/') . '/' . $directory . '/' . $filename;

        $command = sprintf(
            'sqlite3 %s .dump | gzip > %s',
            escapeshellarg($dbPath),
            escapeshellarg($absolutePath)
        );

        return $this->runBackupCommand($command, $disk, $directory . '/' . $filename, $absolutePath);
    }

    protected function unsupportedDriver(string $driver): int
    {
        $this->error("Unsupported database driver for backup: {$driver}");

        return self::FAILURE;
    }

    protected function runBackupCommand(string $command, \Illuminate\Filesystem\FilesystemAdapter $disk, string $relativePath, string $absolutePath): int
    {
        $result = Process::timeout(300)->run($command);

        if ($result->failed()) {
            $this->error('Backup failed: ' . $result->errorOutput());

            return self::FAILURE;
        }

        if (! file_exists($absolutePath)) {
            $this->error('Backup file was not created.');

            return self::FAILURE;
        }

        $sizeKb = round(filesize($absolutePath) / 1024, 1);
        $this->info("Backup created: {$relativePath} ({$sizeKb} KB)");

        // Cleanup old backups
        $this->cleanupOldBackups($disk, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    protected function cleanupOldBackups(\Illuminate\Filesystem\FilesystemAdapter $disk, int $keepDays): void
    {
        $cutoff = Carbon::now()->subDays($keepDays)->timestamp;
        $deleted = 0;

        foreach ($disk->allFiles('database') as $file) {
            if ((str_ends_with($file, '.sql.gz') || str_ends_with($file, '.sqlite.gz')) && $disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} backup(s) older than {$keepDays} days.");
        }
    }
}
