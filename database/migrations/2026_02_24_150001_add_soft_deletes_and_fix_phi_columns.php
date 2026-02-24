<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Add `deleted_at` (SoftDeletes) to all critical clinical & financial tables.
 * 2. Widen PHI columns from string→text so encrypted ciphertext fits.
 * 3. Drop now-useless B-tree indexes on encrypted PHI columns.
 * 4. Add missing FK indexes discovered in audit.
 */
return new class extends Migration
{
    // ──────────────────────────────────────────────
    // Tables that need SoftDeletes (deleted_at)
    // ──────────────────────────────────────────────
    private array $softDeleteTables = [
        'prescriptions',
        'dispensations',
        'drugs',
        'drug_batches',
        'payments',
        'lab_test_catalog',
        'lab_departments',
        'lab_sample_types',
        'queue_departments',
        'queue_counters',
    ];

    // ──────────────────────────────────────────────
    // PHI columns to widen: string → text
    // (Already-text columns are not listed here)
    // ──────────────────────────────────────────────
    private array $phiColumnsToWiden = [
        'patients' => ['cnic', 'phone', 'email', 'city'],
        'patient_contacts' => ['name', 'phone'],
        'patient_allergies' => ['allergen'],
        'patient_chronic_conditions' => ['condition_name'],
        'lab_results' => ['value'],
    ];

    // ──────────────────────────────────────────────
    // Indexes on encrypted columns to drop
    // ──────────────────────────────────────────────
    private array $indexesToDrop = [
        'patients' => [
            'patients_phone_index',
            'patients_cnic_index',
        ],
    ];

    // ──────────────────────────────────────────────
    // Missing FK indexes to add
    // ──────────────────────────────────────────────
    private array $missingIndexes = [
        'visit_diagnoses' => ['icd_code_id'],
        'patient_chronic_conditions' => ['icd_code_id'],
        'lab_order_tests' => ['verified_by'],
        'lab_samples' => ['lab_order_test_id'],
        'payments' => ['received_by'],
        'stock_movements' => ['performed_by'],
        'dispensations' => ['prescription_id', 'dispensed_by'],
        'queue_tickets' => ['patient_id', 'visit_id', 'created_by'],
        'lab_test_catalog' => ['department_id', 'sample_type_id'],
    ];

    public function up(): void
    {
        // 1. Add SoftDeletes
        foreach ($this->softDeleteTables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }

        // 2. Widen PHI columns
        foreach ($this->phiColumnsToWiden as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->text($column)->nullable()->change();
                }
            });
        }

        // 3. Drop indexes on encrypted PHI columns
        foreach ($this->indexesToDrop as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $indexName) {
                    $blueprint->dropIndex($indexName);
                }
            });
        }

        // 4. Add missing FK indexes
        foreach ($this->missingIndexes as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    $indexName = "{$table}_{$column}_index";
                    if (! $this->indexExists($table, $indexName)) {
                        $blueprint->index($column, $indexName);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Reverse 4: Drop added indexes
        foreach ($this->missingIndexes as $table => $columns) {
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

        // Reverse 3: Re-create dropped indexes (on PHI columns — only useful before encryption)
        foreach ($this->indexesToDrop as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                // These were originally on patients.phone and patients.cnic
                if ($table === 'patients') {
                    $blueprint->index('phone', 'patients_phone_index');
                    $blueprint->index('cnic', 'patients_cnic_index');
                }
            });
        }

        // Reverse 2: Revert PHI columns to string
        foreach ($this->phiColumnsToWiden as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->string($column)->nullable()->change();
                }
            });
        }

        // Reverse 1: Drop SoftDeletes columns
        foreach ($this->softDeleteTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
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
