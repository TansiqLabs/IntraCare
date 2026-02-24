<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| Cron entry required on the server:
|   * * * * * cd /path/to/intracare && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Daily database backup at 2:00 AM
Schedule::command('db:backup --keep=30')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Prune stale audit logs older than 5 years (if needed)
// Schedule::command('model:prune', ['--model' => \App\Models\AuditLog::class])->monthly();

// Clear expired password reset tokens weekly
Schedule::command('auth:clear-resets')->weekly();

// Scout sync (Meilisearch) - re-index patients every night
Schedule::command('scout:import', ['App\\Models\\Patient'])
    ->dailyAt('03:00')
    ->withoutOverlapping();
