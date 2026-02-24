<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Enums\ParameterFieldType;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestParameter extends Model
{
    use HasUlid;

    protected $fillable = [
        'lab_test_id',
        'name',
        'unit',
        'normal_range_male',
        'normal_range_female',
        'normal_range_child',
        'method',
        'field_type',
        'field_options',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'field_type' => ParameterFieldType::class,
            'field_options' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTestCatalog::class, 'lab_test_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the normal range based on patient gender and age.
     */
    public function getNormalRangeFor(Gender|string|null $gender, ?int $age = null): ?string
    {
        $genderValue = $gender instanceof Gender ? $gender->value : $gender;

        if ($age !== null && $age < 18) {
            return $this->normal_range_child
                ?? ($genderValue === 'female' ? $this->normal_range_female : null)
                ?? $this->normal_range_male;
        }

        return match ($genderValue) {
            'female' => $this->normal_range_female ?? $this->normal_range_male,
            default => $this->normal_range_male,
        };
    }
}
