<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabDepartment extends Model
{
    use Auditable, HasUlid, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (LabDepartment $dept) {
            if ($dept->tests()->exists()) {
                throw new \RuntimeException(__('Cannot delete a department that has tests.'));
            }
        });
    }

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
