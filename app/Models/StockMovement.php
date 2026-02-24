<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use Auditable, HasUlid;

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Stock movements cannot be modified.');
        });
        static::deleting(function () {
            throw new \RuntimeException('Stock movements cannot be deleted.');
        });
    }

    protected $fillable = [
        'drug_id',
        'drug_batch_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'performed_by',
        'occurred_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DrugBatch::class, 'drug_batch_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
