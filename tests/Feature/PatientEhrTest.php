<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Gender;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\PatientContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PatientEhrTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_patient_and_audit_log_is_written(): void
    {
        $patient = null;

        Patient::withoutSyncingToSearch(function () use (&$patient) {
            $patient = Patient::create([
                'mr_number' => 'MR-000001',
                'first_name' => 'Nazim',
                'last_name' => 'Uddin',
                'gender' => Gender::Male,
            ]);
        });

        $this->assertNotNull($patient->id);
        $this->assertSame('MR-000001', $patient->mr_number);

        $log = AuditLog::query()
            ->where('auditable_type', $patient->getMorphClass())
            ->where('auditable_id', $patient->getKey())
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log, 'Expected an audit log row for patient creation.');
    }

    public function test_patient_contacts_relationship_works(): void
    {
        $patient = null;

        Patient::withoutSyncingToSearch(function () use (&$patient) {
            $patient = Patient::create([
                'mr_number' => 'MR-000002',
                'first_name' => 'A',
                'last_name' => 'B',
                'gender' => Gender::Female,
            ]);
        });

        PatientContact::create([
            'patient_id' => $patient->id,
            'name' => 'Emergency Contact',
            'relation' => 'Brother',
            'phone' => '01700000000',
            'is_emergency' => true,
        ]);

        $this->assertCount(1, $patient->contacts);
        $this->assertSame('Emergency Contact', $patient->contacts->first()->name);
    }
}
