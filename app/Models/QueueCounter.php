<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueueCounter extends Model
{
    use Auditable, HasUlid, SoftDeletes;

    protected $fillable = [
        'queue_department_id',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(QueueDepartment::class, 'queue_department_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}
