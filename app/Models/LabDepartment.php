<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabDepartment extends Model
{
    use Auditable, HasUlid;

    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(LabTestCatalog::class, 'department_id');
    }
}
