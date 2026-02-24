<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drug extends Model
{
    use HasUlid;

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
        return (int) $this->batches()->sum('quantity_on_hand');
    }

    public function isLowStock(): bool
    {
        return $this->reorder_level > 0 && $this->total_on_hand <= $this->reorder_level;
    }
}
