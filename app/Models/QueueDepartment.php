<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueDepartment extends Model
{
    use HasUlid;

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
}
