<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    use Auditable, HasUlid;

    protected $fillable = [
        'lab_order_test_id',
        'lab_test_parameter_id',
        'value',
        'is_abnormal',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_abnormal' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function labOrderTest(): BelongsTo
    {
        return $this->belongsTo(LabOrderTest::class);
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(LabTestParameter::class, 'lab_test_parameter_id');
    }
}
