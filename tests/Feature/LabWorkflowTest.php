<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Gender;
use App\Models\AuditLog;
use App\Models\LabDepartment;
use App\Models\LabOrder;
use App\Models\LabOrderTest;
use App\Models\LabSampleType;
use App\Models\LabTestCatalog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LabWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_lab_order_and_tests_and_audit_log_is_written(): void
    {
        $doctor = User::factory()->create();

        $patient = null;
        Patient::withoutSyncingToSearch(function () use (&$patient) {
            $patient = Patient::create([
                'mr_number' => 'MR-000010',
                'first_name' => 'Lab',
                'last_name' => 'Patient',
                'gender' => Gender::Male,
            ]);
        });

        $dept = LabDepartment::create([
            'name' => 'Hematology',
            'is_active' => true,
        ]);

        $sampleType = LabSampleType::create([
            'name' => 'Blood',
            'is_active' => true,
        ]);

        $test = LabTestCatalog::create([
            'department_id' => $dept->id,
            'sample_type_id' => $sampleType->id,
            'code' => 'CBC',
            'name' => 'Complete Blood Count',
            'cost' => 50000,
            'is_active' => true,
        ]);

        $order = LabOrder::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'order_number' => 'LO-20260224-0001',
            'status' => 'pending_payment',
            'priority' => 'routine',
        ]);

        $orderTest = LabOrderTest::create([
            'lab_order_id' => $order->id,
            'lab_test_id' => $test->id,
            'status' => 'pending',
        ]);

        $this->assertSame($patient->id, $order->patient->id);
        $this->assertCount(1, $order->orderTests);
        $this->assertSame($orderTest->id, $order->orderTests->first()->id);

        $log = AuditLog::query()
            ->where('auditable_type', $order->getMorphClass())
            ->where('auditable_id', $order->getKey())
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log, 'Expected audit log row for lab order creation.');
    }
}
