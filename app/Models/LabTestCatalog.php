<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTestCatalog extends Model
{
    use HasUlid;

    protected $table = 'lab_test_catalog';

    protected $fillable = [
        'department_id',
        'sample_type_id',
        'code',
        'name',
        'short_name',
        'cost',
        'turn_around_minutes',
        'is_active',
        'sort_order',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'turn_around_minutes' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    /**
     * Get cost in major currency unit (e.g. rupees from paisa).
     */
    public function getFormattedCostAttribute(): string
    {
        return number_format($this->cost / 100, 2);
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function department(): BelongsTo
    {
        return $this->belongsTo(LabDepartment::class, 'department_id');
    }

    public function sampleType(): BelongsTo
    {
        return $this->belongsTo(LabSampleType::class, 'sample_type_id');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(LabTestParameter::class, 'lab_test_id')->orderBy('sort_order');
    }
}
