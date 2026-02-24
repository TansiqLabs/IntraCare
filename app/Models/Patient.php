<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Patient extends Model
{
    use Auditable, HasFactory, HasUlid, Searchable, SoftDeletes;

    protected $fillable = [
        'mr_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'cnic',
        'phone',
        'email',
        'address',
        'city',
        'photo_path',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => Gender::class,
            'blood_group' => BloodGroup::class,
        ];
    }

    // ──────────────────────────────────────────────
    // Meilisearch Configuration
    // ──────────────────────────────────────────────

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'mr_number' => $this->mr_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => "{$this->first_name} {$this->last_name}",
            'phone' => $this->phone,
            'cnic' => $this->cnic,
            'email' => $this->email,
            'city' => $this->city,
        ];
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PatientContact::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function chronicConditions(): HasMany
    {
        return $this->hasMany(PatientChronicCondition::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function dispensations(): HasMany
    {
        return $this->hasMany(Dispensation::class);
    }

    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}
