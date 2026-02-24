<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueueDepartment extends Model
{
    use Auditable, HasUlid, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (QueueDepartment $dept) {
            if ($dept->tickets()->exists()) {
                throw new \RuntimeException(__('Cannot delete a department that has tickets. Deactivate it instead.'));
            }
            if ($dept->counters()->exists()) {
                throw new \RuntimeException(__('Cannot delete a department that has counters. Deactivate it instead.'));
            }
        });
    }

    protected $fillable = [
        'name',
        'code',
        'floor',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function counters(): HasMany
    {
        return $this->hasMany(QueueCounter::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    public function dailySequences(): HasMany
    {
        return $this->hasMany(QueueDailySequence::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
