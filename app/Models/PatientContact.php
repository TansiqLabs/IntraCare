<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientContact extends Model
{
    use Auditable, HasUlid;

    protected $fillable = [
        'patient_id',
        'name',
        'relation',
        'phone',
        'is_emergency',
    ];

    protected function casts(): array
    {
        return [
            'is_emergency' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
