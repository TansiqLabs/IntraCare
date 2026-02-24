<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueTicket extends Model
{
    use Auditable, HasUlid;

    protected $fillable = [
        'queue_department_id',
        'queue_counter_id',
        'patient_id',
        'visit_id',
        'token_date',
        'token_number',
        'token_display',
        'status',
        'called_at',
        'served_at',
        'no_show_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'token_date' => 'date',
            'token_number' => 'integer',
            'called_at' => 'datetime',
            'served_at' => 'datetime',
            'no_show_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(QueueDepartment::class, 'queue_department_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(QueueCounter::class, 'queue_counter_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForToday(Builder $query): Builder
    {
        return $query->whereDate('token_date', now()->toDateString());
    }
}
