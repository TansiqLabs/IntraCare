<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueDailySequence extends Model
{
    use HasUlid;

    protected $fillable = [
        'queue_department_id',
        'token_date',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'token_date' => 'date',
            'last_number' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(QueueDepartment::class, 'queue_department_id');
    }
}
