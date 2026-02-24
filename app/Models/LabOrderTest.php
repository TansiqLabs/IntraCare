<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabOrderTestStatus;
use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabOrderTest extends Model
{
    use Auditable, HasUlid;

    protected $fillable = [
        'lab_order_id',
        'lab_test_id',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LabOrderTestStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTestCatalog::class, 'lab_test_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function sample(): HasOne
    {
        return $this->hasOne(LabSample::class)->latestOfMany();
    }

    public function samples(): HasMany
    {
        return $this->hasMany(LabSample::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }
}
