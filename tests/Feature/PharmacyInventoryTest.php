<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DispensationStatus;
use App\Enums\StockMovementType;
use App\Models\Dispensation;
use App\Models\DispensationItem;
use App\Models\Drug;
use App\Models\DrugBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Pharmacy\PharmacyInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PharmacyInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_receive_stock_to_batch_and_stock_movement_is_written(): void
    {
        $user = User::factory()->create();

        $drug = Drug::create([
            'generic_name' => 'Paracetamol',
            'brand_name' => 'Napa',
            'formulation' => 'tablet',
            'strength' => '500mg',
            'unit' => 'pcs',
            'barcode' => '1234567890',
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        $service = app(PharmacyInventoryService::class);

        $batch = $service->receiveToBatch(
            drug: $drug,
            batchNumber: 'BATCH-001',
            quantity: 100,
            unitCost: 50,
            salePrice: 100,
            supplierName: 'Local Supplier',
            performedBy: $user->getKey(),
        );

        $this->assertSame('BATCH-001', $batch->batch_number);
        $this->assertSame(100, (int) $batch->quantity_received);
        $this->assertSame(100, (int) $batch->quantity_on_hand);

        $movement = StockMovement::query()->where('drug_batch_id', $batch->getKey())->first();
        $this->assertNotNull($movement);
        $this->assertSame(StockMovementType::Receive, $movement->type);
        $this->assertSame(100, (int) $movement->quantity);
    }

    public function test_receive_to_batch_adds_stock_to_existing_batch(): void
    {
        $user = User::factory()->create();

        $drug = Drug::create([
            'generic_name' => 'Amoxicillin',
            'is_active' => true,
        ]);

        $service = app(PharmacyInventoryService::class);

        // First receive creates the batch
        $batch = $service->receiveToBatch(
            drug: $drug,
            batchNumber: 'AMX-001',
            quantity: 50,
            unitCost: 40,
            salePrice: 80,
            performedBy: $user->getKey(),
        );

        $this->assertSame(50, (int) $batch->quantity_on_hand);

        // Second receive adds to the same batch
        $batch2 = $service->receiveToBatch(
            drug: $drug,
            batchNumber: 'AMX-001',
            quantity: 30,
            unitCost: 40,
            salePrice: 80,
            performedBy: $user->getKey(),
        );

        $this->assertTrue($batch->is($batch2), 'Expected same batch record.');
        $batch->refresh();
        $this->assertSame(80, (int) $batch->quantity_received);
        $this->assertSame(80, (int) $batch->quantity_on_hand);
        $this->assertSame(2, StockMovement::query()->where('drug_batch_id', $batch->getKey())->where('type', 'receive')->count());
    }

    public function test_fefo_skips_expired_batches(): void
    {
        $user = User::factory()->create();

        $drug = Drug::create([
            'generic_name' => 'Ibuprofen',
            'is_active' => true,
        ]);

        // Expired batch (should be skipped)
        DrugBatch::create([
            'drug_id' => $drug->getKey(),
            'batch_number' => 'IBU-EXPIRED',
            'expiry_date' => now()->subDay()->toDateString(),
            'quantity_received' => 50,
            'quantity_on_hand' => 50,
            'unit_cost' => 30,
            'sale_price' => 60,
            'received_at' => now()->subMonth(),
            'is_active' => true,
        ]);

        // Valid batch
        $validBatch = DrugBatch::create([
            'drug_id' => $drug->getKey(),
            'batch_number' => 'IBU-VALID',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'quantity_received' => 20,
            'quantity_on_hand' => 20,
            'unit_cost' => 30,
            'sale_price' => 60,
            'received_at' => now(),
            'is_active' => true,
        ]);

        $dispensation = Dispensation::create(['status' => 'draft']);

        DispensationItem::create([
            'dispensation_id' => $dispensation->getKey(),
            'drug_id' => $drug->getKey(),
            'quantity' => 10,
            'unit_price' => 0,
            'line_total' => 0,
        ]);

        $service = app(PharmacyInventoryService::class);
        $completed = $service->completeDispensation($dispensation, performedBy: $user->getKey());

        $completed->load('items');
        $this->assertCount(1, $completed->items, 'Only the valid batch should be used.');
        $this->assertSame($validBatch->getKey(), $completed->items->first()->drug_batch_id);

        $validBatch->refresh();
        $this->assertSame(10, (int) $validBatch->quantity_on_hand);
    }

    public function test_complete_dispensation_allocates_fefo_and_deducts_stock(): void
    {
        $user = User::factory()->create();

        $drug = Drug::create([
            'generic_name' => 'Omeprazole',
            'brand_name' => null,
            'formulation' => 'capsule',
            'strength' => '20mg',
            'unit' => 'pcs',
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        $batch1 = DrugBatch::create([
            'drug_id' => $drug->getKey(),
            'batch_number' => 'B1',
            'expiry_date' => now()->addDays(10)->toDateString(),
            'quantity_received' => 5,
            'quantity_on_hand' => 5,
            'unit_cost' => 80,
            'sale_price' => 100,
            'supplier_name' => null,
            'received_at' => now()->subDays(2),
            'is_active' => true,
        ]);

        $batch2 = DrugBatch::create([
            'drug_id' => $drug->getKey(),
            'batch_number' => 'B2',
            'expiry_date' => now()->addDays(30)->toDateString(),
            'quantity_received' => 10,
            'quantity_on_hand' => 10,
            'unit_cost' => 80,
            'sale_price' => 120,
            'supplier_name' => null,
            'received_at' => now()->subDays(1),
            'is_active' => true,
        ]);

        $dispensation = Dispensation::create([
            'status' => 'draft',
        ]);

        DispensationItem::create([
            'dispensation_id' => $dispensation->getKey(),
            'drug_id' => $drug->getKey(),
            'drug_batch_id' => null,
            'quantity' => 8,
            'unit_price' => 0,
            'line_total' => 0,
        ]);

        $service = app(PharmacyInventoryService::class);

        $completed = $service->completeDispensation($dispensation, performedBy: $user->getKey());

        $this->assertSame(DispensationStatus::Completed, $completed->status);
        $this->assertNotNull($completed->dispensed_at);

        $completed->load('items');

        $this->assertCount(2, $completed->items, 'Expected FEFO allocation across two batches.');

        $byBatch = $completed->items->keyBy('drug_batch_id');

        $this->assertSame(5, (int) $byBatch[$batch1->getKey()]->quantity);
        $this->assertSame(100, (int) $byBatch[$batch1->getKey()]->unit_price);
        $this->assertSame(500, (int) $byBatch[$batch1->getKey()]->line_total);

        $this->assertSame(3, (int) $byBatch[$batch2->getKey()]->quantity);
        $this->assertSame(120, (int) $byBatch[$batch2->getKey()]->unit_price);
        $this->assertSame(360, (int) $byBatch[$batch2->getKey()]->line_total);

        $batch1->refresh();
        $batch2->refresh();

        $this->assertSame(0, (int) $batch1->quantity_on_hand);
        $this->assertSame(7, (int) $batch2->quantity_on_hand);

        $this->assertSame(2, StockMovement::query()->where('reference_id', $dispensation->getKey())->where('type', 'dispense')->count());
    }

    public function test_complete_dispensation_fails_when_insufficient_stock_and_rolls_back(): void
    {
        $user = User::factory()->create();

        $drug = Drug::create([
            'generic_name' => 'Cetirizine',
            'is_active' => true,
        ]);

        $batch = DrugBatch::create([
            'drug_id' => $drug->getKey(),
            'batch_number' => 'CET-1',
            'expiry_date' => now()->addDays(90)->toDateString(),
            'quantity_received' => 3,
            'quantity_on_hand' => 3,
            'unit_cost' => 50,
            'sale_price' => 80,
            'received_at' => now(),
            'is_active' => true,
        ]);

        $dispensation = Dispensation::create([
            'status' => 'draft',
        ]);

        DispensationItem::create([
            'dispensation_id' => $dispensation->getKey(),
            'drug_id' => $drug->getKey(),
            'quantity' => 5,
            'unit_price' => 0,
            'line_total' => 0,
        ]);

        $service = app(PharmacyInventoryService::class);

        $this->expectException(\RuntimeException::class);

        try {
            $service->completeDispensation($dispensation, performedBy: $user->getKey());
        } finally {
            $batch->refresh();
            $this->assertSame(3, (int) $batch->quantity_on_hand, 'Expected rollback to preserve stock.');
            $this->assertSame(0, StockMovement::query()->where('type', 'dispense')->count());
        }
    }

    public function test_void_completed_dispensation_restores_stock_and_writes_return_movements(): void
    {
        $user = User::factory()->create();

        $drug = Drug::create([
            'generic_name' => 'Azithromycin',
            'is_active' => true,
        ]);

        $batch1 = DrugBatch::create([
            'drug_id' => $drug->getKey(),
            'batch_number' => 'AZI-1',
            'expiry_date' => now()->addDays(10)->toDateString(),
            'quantity_received' => 5,
            'quantity_on_hand' => 5,
            'unit_cost' => 50,
            'sale_price' => 80,
            'received_at' => now()->subDays(2),
            'is_active' => true,
        ]);

        $batch2 = DrugBatch::create([
            'drug_id' => $drug->getKey(),
            'batch_number' => 'AZI-2',
            'expiry_date' => now()->addDays(30)->toDateString(),
            'quantity_received' => 10,
            'quantity_on_hand' => 10,
            'unit_cost' => 50,
            'sale_price' => 90,
            'received_at' => now()->subDays(1),
            'is_active' => true,
        ]);

        $dispensation = Dispensation::create([
            'status' => 'draft',
        ]);

        DispensationItem::create([
            'dispensation_id' => $dispensation->getKey(),
            'drug_id' => $drug->getKey(),
            'quantity' => 8,
            'unit_price' => 0,
            'line_total' => 0,
        ]);

        $service = app(PharmacyInventoryService::class);

        $completed = $service->completeDispensation($dispensation, performedBy: $user->getKey());

        $batch1->refresh();
        $batch2->refresh();
        $this->assertSame(0, (int) $batch1->quantity_on_hand);
        $this->assertSame(7, (int) $batch2->quantity_on_hand);

        $voided = $service->voidCompletedDispensation($completed, performedBy: $user->getKey(), reason: 'Wrong patient');
        $this->assertSame(DispensationStatus::Cancelled, $voided->status);

        $batch1->refresh();
        $batch2->refresh();
        $this->assertSame(5, (int) $batch1->quantity_on_hand);
        $this->assertSame(10, (int) $batch2->quantity_on_hand);

        $this->assertSame(2, StockMovement::query()->where('reference_id', $dispensation->getKey())->where('type', 'return')->count());
    }
}
