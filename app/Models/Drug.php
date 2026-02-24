<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use Auditable, HasUlid, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Drug $drug) {
            if (! $drug->isForceDeleting()) {
                if ($drug->batches()->exists()) {
                    throw new \RuntimeException(__('Cannot delete a drug that has batches. Deactivate it instead.'));
                }
                if ($drug->dispensationItems()->exists()) {
                    throw new \RuntimeException(__('Cannot delete a drug that has dispensation items.'));
                }
            }
        });

        static::forceDeleting(function (Drug $model) {
            throw new \RuntimeException('Force-deleting drug records is prohibited.');
        });
    }

    protected $fillable = [
        'generic_name',
        'brand_name',
        'formulation',
        'strength',
        'unit',
        'barcode',
        'is_active',
        'reorder_level',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'reorder_level' => 'integer',
        ];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(DrugBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function dispensationItems(): HasMany
    {
        return $this->hasMany(DispensationItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = [trim((string) $this->generic_name)];

        if ($this->brand_name) {
            $parts[] = "({$this->brand_name})";
        }

        if ($this->strength) {
            $parts[] = $this->strength;
        }

        if ($this->formulation) {
            $parts[] = $this->formulation;
        }

        return trim(implode(' ', $parts));
    }

    public function getTotalOnHandAttribute(): int
    {
        return (int) $this->batches()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->sum('quantity_on_hand');
    }

    public function isLowStock(): bool
    {
        return $this->reorder_level > 0 && $this->total_on_hand <= $this->reorder_level;
    }
}
