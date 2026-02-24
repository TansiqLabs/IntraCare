<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IcdVersion;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class IcdCode extends Model
{
    use HasUlid;

    protected $fillable = [
        'code',
        'short_description',
        'long_description',
        'version',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'version' => IcdVersion::class,
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeIcd10($query)
    {
        return $query->where('version', IcdVersion::Icd10);
    }

    public function scopeIcd11($query)
    {
        return $query->where('version', IcdVersion::Icd11);
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function visitDiagnoses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VisitDiagnosis::class);
    }

    public function chronicConditions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientChronicCondition::class);
    }
}
