<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabOrderStatus;
use App\Enums\LabPriority;
use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabOrder extends Model
{
    use Auditable, HasUlid, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'visit_id',
        'doctor_id',
        'order_number',
        'status',
        'priority',
        'clinical_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => LabOrderStatus::class,
            'priority' => LabPriority::class,
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function orderTests(): HasMany
    {
        return $this->hasMany(LabOrderTest::class);
    }

    public function samples(): HasMany
    {
        return $this->hasMany(LabSample::class);
    }
}
