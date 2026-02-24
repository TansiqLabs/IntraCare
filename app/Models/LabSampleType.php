<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabSampleType extends Model
{
    use HasUlid;

    protected $fillable = [
        'name',
        'description',
        'container_color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(LabTestCatalog::class, 'sample_type_id');
    }
}
