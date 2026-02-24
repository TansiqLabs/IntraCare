<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DispensationStatus;
use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispensation extends Model
{
    use Auditable, HasUlid, SoftDeletes;

    protected static function booted(): void
    {
        static::forceDeleting(function (Dispensation $model) {
            throw new \RuntimeException('Force-deleting dispensation records is prohibited.');
        });
    }

    protected $fillable = [
        'patient_id',
        'prescription_id',
        'status',
        'dispensed_by',
        'dispensed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DispensationStatus::class,
            'dispensed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispensationItem::class);
    }
}
