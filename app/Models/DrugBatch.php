<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrugBatch extends Model
{
    use HasUlid;

    protected $fillable = [
        'drug_id',
        'batch_number',
        'expiry_date',
        'quantity_received',
        'quantity_on_hand',
        'unit_cost',
        'sale_price',
        'supplier_name',
        'received_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'received_at' => 'datetime',
            'quantity_received' => 'integer',
            'quantity_on_hand' => 'integer',
            'unit_cost' => 'integer',
            'sale_price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'drug_batch_id');
    }

    public function dispensationItems(): HasMany
    {
        return $this->hasMany(DispensationItem::class, 'drug_batch_id');
    }
}
