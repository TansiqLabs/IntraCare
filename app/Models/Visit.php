<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use Auditable, HasFactory, HasUlid, SoftDeletes;

    protected static function booted(): void
    {
        static::forceDeleting(function (Visit $model) {
            throw new \RuntimeException('Force-deleting visit records is prohibited.');
        });
    }

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'visit_number',
        'visit_type',
        'status',
        'chief_complaint',
        'examination_notes',
        'plan',
        'vitals',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_type' => VisitType::class,
            'status' => VisitStatus::class,
            'vitals' => 'array',
            'visited_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(VisitDiagnosis::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}
