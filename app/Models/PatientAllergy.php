<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AllergySeverity;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAllergy extends Model
{
    use HasUlid;

    protected $fillable = [
        'patient_id',
        'allergen',
        'severity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'severity' => AllergySeverity::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
