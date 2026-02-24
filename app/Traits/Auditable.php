<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait Auditable
 *
 * Add this trait to any Eloquent model that requires
 * HIPAA-inspired audit logging on CRUD operations.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            static::logAudit($model, 'created', [], $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $original = $model->getOriginal();
            $changed = $model->getChanges();

            // Remove timestamps from diff to reduce noise
            $ignored = ['updated_at', 'remember_token'];
            $oldValues = [];
            $newValues = [];

            foreach ($changed as $key => $value) {
                if (in_array($key, $ignored, true)) {
                    continue;
                }
                $oldValues[$key] = $original[$key] ?? null;
                $newValues[$key] = $value;
            }

            if (! empty($newValues)) {
                static::logAudit($model, 'updated', $oldValues, $newValues);
            }
        });

        static::deleted(function (Model $model) {
            $event = (method_exists($model, 'isForceDeleting') && $model->isForceDeleting())
                ? 'force_deleted'
                : 'deleted';
            static::logAudit($model, $event, $model->getAttributes(), []);
        });

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function (Model $model) {
                static::logAudit($model, 'restored', [], $model->getAttributes());
            });
        }
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    protected static function logAudit(Model $model, string $event, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => ! empty($oldValues) ? $oldValues : null,
            'new_values' => ! empty($newValues) ? $newValues : null,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            // AuditLog has $timestamps=false; created_at is required by schema.
            'created_at' => now(),
        ]);
    }
}
