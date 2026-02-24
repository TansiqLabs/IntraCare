<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SampleStatus;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabSample extends Model
{
    use HasUlid;

    protected $fillable = [
        'lab_order_id',
        'lab_order_test_id',
        'barcode',
        'collected_by',
        'collected_at',
        'status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => SampleStatus::class,
            'collected_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function labOrderTest(): BelongsTo
    {
        return $this->belongsTo(LabOrderTest::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
