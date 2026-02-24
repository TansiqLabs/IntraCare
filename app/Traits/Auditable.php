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
    /**
     * Attribute names to exclude from audit log values (e.g. password hashes).
     * Override in models to add more.
     */
    protected function auditExclude(): array
    {
        return [];
    }

    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $exclude = static::resolveAuditExclusions($model);
            $newValues = array_diff_key($model->getAttributes(), array_flip($exclude));
            static::logAudit($model, 'created', [], $newValues);
        });

        static::updated(function (Model $model) {
            $original = $model->getOriginal();
            $changed = $model->getChanges();
            $exclude = static::resolveAuditExclusions($model);

            // Remove timestamps from diff to reduce noise
            $ignored = array_merge(['updated_at', 'remember_token'], $exclude);
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
            $exclude = static::resolveAuditExclusions($model);
            $oldValues = array_diff_key($model->getAttributes(), array_flip($exclude));
            static::logAudit($model, $event, $oldValues, []);
        });

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function (Model $model) {
                $exclude = static::resolveAuditExclusions($model);
                $newValues = array_diff_key($model->getAttributes(), array_flip($exclude));
                static::logAudit($model, 'restored', [], $newValues);
            });
        }
    }

    /**
     * Resolve the combined list of attributes to always exclude from audit logging.
     */
    protected static function resolveAuditExclusions(Model $model): array
    {
        $base = ['password', 'remember_token'];

        if (method_exists($model, 'auditExclude')) {
            return array_merge($base, $model->auditExclude());
        }

        return $base;
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
