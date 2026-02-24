<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientChronicCondition extends Model
{
    use Auditable, HasUlid;

    protected $fillable = [
        'patient_id',
        'condition_name',
        'icd_code_id',
        'diagnosed_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'diagnosed_on' => 'date',
            'condition_name' => 'encrypted',
            'notes' => 'encrypted',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function icdCode(): BelongsTo
    {
        return $this->belongsTo(IcdCode::class);
    }
}
