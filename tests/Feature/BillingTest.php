<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_invoice_and_payment_and_audit_log_is_written(): void
    {
        $cashier = User::factory()->create();

        $patient = null;
        Patient::withoutSyncingToSearch(function () use (&$patient) {
            $patient = Patient::create([
                'mr_number' => 'MR-000020',
                'first_name' => 'Bill',
                'last_name' => 'Patient',
                'gender' => Gender::Other,
            ]);
        });

        $invoice = Invoice::create([
            'invoice_number' => 'INV-20260224-0001',
            'patient_id' => $patient->id,
            'invoice_type' => InvoiceType::Lab,
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 0,
            'total' => 10000,
            'paid' => 0,
            'balance' => 10000,
            'status' => InvoiceStatus::Issued,
            'created_by' => $cashier->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 5000,
            'method' => 'cash',
            'received_by' => $cashier->id,
            'paid_at' => now(),
        ]);

        $this->assertCount(1, $invoice->payments);
        $this->assertSame(5000, $invoice->payments->first()->amount);

        $log = AuditLog::query()
            ->where('auditable_type', $invoice->getMorphClass())
            ->where('auditable_id', $invoice->getKey())
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log, 'Expected audit log row for invoice creation.');
    }
}
