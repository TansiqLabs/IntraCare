<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiagnosisType;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitDiagnosis extends Model
{
    use HasUlid;

    protected $fillable = [
        'visit_id',
        'icd_code_id',
        'diagnosis_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'diagnosis_type' => DiagnosisType::class,
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function icdCode(): BelongsTo
    {
        return $this->belongsTo(IcdCode::class);
    }
}
