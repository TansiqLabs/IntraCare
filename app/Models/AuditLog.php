<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit Log — Append-only. No updates or deletes allowed at application level.
 */
class AuditLog extends Model
{
    use HasUlid;

    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Prevent updating or deleting audit logs at application level
        static::updating(function () {
            throw new \RuntimeException('Audit logs cannot be modified.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Audit logs cannot be deleted.');
        });
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function auditable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
