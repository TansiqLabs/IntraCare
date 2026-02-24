<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add indexes on frequently-queried foreign key columns.
 *
 * PostgreSQL auto-creates indexes for foreign keys, but SQLite does not.
 * Having explicit indexes ensures consistent query performance across both
 * engines and makes the schema self-documenting.
 */
return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'patient_contacts' => ['patient_id'],
            'patient_allergies' => ['patient_id'],
            'patient_chronic_conditions' => ['patient_id'],
            'visits' => ['patient_id', 'doctor_id'],
            'visit_diagnoses' => ['visit_id'],
            'prescriptions' => ['visit_id'],
            'prescription_items' => ['prescription_id'],
            'lab_orders' => ['patient_id', 'visit_id', 'doctor_id'],
            'lab_order_tests' => ['lab_order_id', 'lab_test_id'],
            'lab_test_parameters' => ['lab_test_id'],
            'lab_samples' => ['lab_order_id'],
            'lab_results' => ['lab_order_test_id'],
            'invoices' => ['patient_id', 'visit_id', 'created_by'],
            'invoice_items' => ['invoice_id'],
            'payments' => ['invoice_id'],
            'dispensations' => ['patient_id'],
            'dispensation_items' => ['dispensation_id', 'drug_id', 'drug_batch_id'],
            'drug_batches' => ['drug_id'],
            'stock_movements' => ['drug_id', 'drug_batch_id'],
            'queue_tickets' => ['queue_department_id', 'queue_counter_id'],
            'queue_counters' => ['queue_department_id'],
        ];

        foreach ($indexes as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    $indexName = "{$table}_{$column}_index";

                    // Skip if index already exists (e.g. PostgreSQL auto-indexes)
                    if ($this->indexExists($table, $indexName)) {
                        continue;
                    }

                    $blueprint->index($column, $indexName);
                }
            });
        }
    }

    public function down(): void
    {
        $indexes = [
            'patient_contacts' => ['patient_id'],
            'patient_allergies' => ['patient_id'],
            'patient_chronic_conditions' => ['patient_id'],
            'visits' => ['patient_id', 'doctor_id'],
            'visit_diagnoses' => ['visit_id'],
            'prescriptions' => ['visit_id'],
            'prescription_items' => ['prescription_id'],
            'lab_orders' => ['patient_id', 'visit_id', 'doctor_id'],
            'lab_order_tests' => ['lab_order_id', 'lab_test_id'],
            'lab_test_parameters' => ['lab_test_id'],
            'lab_samples' => ['lab_order_id'],
            'lab_results' => ['lab_order_test_id'],
            'invoices' => ['patient_id', 'visit_id', 'created_by'],
            'invoice_items' => ['invoice_id'],
            'payments' => ['invoice_id'],
            'dispensations' => ['patient_id'],
            'dispensation_items' => ['dispensation_id', 'drug_id', 'drug_batch_id'],
            'drug_batches' => ['drug_id'],
            'stock_movements' => ['drug_id', 'drug_batch_id'],
            'queue_tickets' => ['queue_department_id', 'queue_counter_id'],
            'queue_counters' => ['queue_department_id'],
        ];

        foreach ($indexes as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    $indexName = "{$table}_{$column}_index";
                    if ($this->indexExists($table, $indexName)) {
                        $blueprint->dropIndex($indexName);
                    }
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $result = Schema::getConnection()
                ->select("SELECT name FROM sqlite_master WHERE type='index' AND name=?", [$indexName]);

            return count($result) > 0;
        }

        // PostgreSQL / MySQL
        $schema = Schema::getConnection()->getDatabaseName();

        $result = Schema::getConnection()->select(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1",
            [$schema, $table, $indexName]
        );

        return count($result) > 0;
    }
};
