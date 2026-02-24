<?php

declare(strict_types=1);

namespace App\Services\Pharmacy;

use App\Models\Dispensation;
use App\Models\DispensationItem;
use App\Models\Drug;
use App\Models\DrugBatch;
use App\Models\StockMovement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class PharmacyInventoryService
{
    /**
     * Receive (add) stock into a batch. Creates the batch if it doesn't exist.
     */
    public function receiveToBatch(
        Drug $drug,
        string $batchNumber,
        int $quantity,
        int $unitCost = 0,
        int $salePrice = 0,
        ?string $supplierName = null,
        ?\DateTimeInterface $expiryDate = null,
        ?\DateTimeInterface $receivedAt = null,
        ?string $performedBy = null,
        ?string $notes = null,
    ): DrugBatch {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Receive quantity must be greater than zero.');
        }

        return DB::transaction(function () use (
            $drug,
            $batchNumber,
            $quantity,
            $unitCost,
            $salePrice,
            $supplierName,
            $expiryDate,
            $receivedAt,
            $performedBy,
            $notes,
        ) {
            $batch = DrugBatch::query()
                ->where('drug_id', $drug->getKey())
                ->where('batch_number', $batchNumber)
                ->lockForUpdate()
                ->first();

            if (! $batch) {
                $batch = DrugBatch::create([
                    'drug_id' => $drug->getKey(),
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate?->format('Y-m-d'),
                    'quantity_received' => 0,
                    'quantity_on_hand' => 0,
                    'unit_cost' => $unitCost,
                    'sale_price' => $salePrice,
                    'supplier_name' => $supplierName,
                    'received_at' => $receivedAt ?? now(),
                    'is_active' => true,
                ]);

                // Lock newly created row for the rest of the transaction.
                $batch->refresh();
                DrugBatch::query()->whereKey($batch->getKey())->lockForUpdate()->first();
            }

            $batch->quantity_received = (int) $batch->quantity_received + $quantity;
            $batch->quantity_on_hand = (int) $batch->quantity_on_hand + $quantity;

            if ($expiryDate) {
                $batch->expiry_date = $expiryDate->format('Y-m-d');
            }
            if ($supplierName !== null) {
                $batch->supplier_name = $supplierName;
            }
            if ($receivedAt !== null) {
                $batch->received_at = $receivedAt;
            }
            if ($unitCost !== 0) {
                $batch->unit_cost = $unitCost;
            }
            if ($salePrice !== 0) {
                $batch->sale_price = $salePrice;
            }

            $batch->save();

            StockMovement::create([
                'drug_id' => $drug->getKey(),
                'drug_batch_id' => $batch->getKey(),
                'type' => 'receive',
                'quantity' => $quantity,
                'reference_type' => DrugBatch::class,
                'reference_id' => $batch->getKey(),
                'performed_by' => $performedBy,
                'occurred_at' => $receivedAt ?? now(),
                'notes' => $notes,
            ]);

            return $batch->refresh();
        }, 3);
    }

    /**
     * Complete a draft dispensation by allocating items from stock (FEFO) and writing stock movements.
     *
     * This method is concurrency-safe: it locks the dispensation row and all affected batch rows.
     */
    public function completeDispensation(
        Dispensation $dispensation,
        ?string $performedBy = null,
        ?\DateTimeInterface $dispensedAt = null,
        bool $excludeExpired = true,
    ): Dispensation {
        $dispensedAt = $dispensedAt ?? now();

        return DB::transaction(function () use ($dispensation, $performedBy, $dispensedAt, $excludeExpired) {
            $dispensation = Dispensation::query()->whereKey($dispensation->getKey())->lockForUpdate()->firstOrFail();

            if ($dispensation->status === 'completed') {
                return $dispensation->load('items');
            }

            if ($dispensation->status !== 'draft') {
                throw new \RuntimeException('Only draft dispensations can be completed.');
            }

            $existingItems = $dispensation->items()->get();
            if ($existingItems->isEmpty()) {
                throw new \RuntimeException('Dispensation has no items.');
            }

            $requests = $existingItems->map(fn (DispensationItem $item) => [
                'drug_id' => $item->drug_id,
                'drug_batch_id' => $item->drug_batch_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => $item->unit_price ?: null,
                'notes' => $item->notes,
            ])->all();

            // We will re-create the items based on actual allocation (can split across batches).
            $dispensation->items()->delete();

            $allocated = [];

            foreach ($requests as $req) {
                $drugId = (string) $req['drug_id'];
                $qty = (int) $req['quantity'];

                if ($qty <= 0) {
                    throw new \RuntimeException('Dispense quantity must be greater than zero.');
                }

                $forcedBatchId = Arr::get($req, 'drug_batch_id');

                if ($forcedBatchId) {
                    $batch = DrugBatch::query()->whereKey($forcedBatchId)->lockForUpdate()->firstOrFail();

                    if ((int) $batch->quantity_on_hand < $qty) {
                        throw new \RuntimeException('Insufficient stock in selected batch.');
                    }

                    $unitPrice = (int) (Arr::get($req, 'unit_price') ?? $batch->sale_price ?? 0);

                    $this->deductFromBatch(
                        batch: $batch,
                        quantity: $qty,
                        occurredAt: $dispensedAt,
                        performedBy: $performedBy,
                        referenceType: Dispensation::class,
                        referenceId: $dispensation->getKey(),
                        notes: Arr::get($req, 'notes'),
                    );

                    $allocated[] = [
                        'drug_id' => $drugId,
                        'drug_batch_id' => $batch->getKey(),
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'line_total' => $unitPrice * $qty,
                        'notes' => Arr::get($req, 'notes'),
                    ];

                    continue;
                }

                // FEFO allocation.
                $remaining = $qty;

                $batchQuery = DrugBatch::query()
                    ->where('drug_id', $drugId)
                    ->where('is_active', true)
                    ->where('quantity_on_hand', '>', 0);

                if ($excludeExpired) {
                    $batchQuery->where(function ($q) {
                        $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
                    });
                }

                // Order by earliest expiry first; NULL expiry last.
                $batches = $batchQuery
                    ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('expiry_date')
                    ->orderBy('received_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $take = min($remaining, (int) $batch->quantity_on_hand);
                    if ($take <= 0) {
                        continue;
                    }

                    $unitPrice = (int) (Arr::get($req, 'unit_price') ?? $batch->sale_price ?? 0);

                    $this->deductFromBatch(
                        batch: $batch,
                        quantity: $take,
                        occurredAt: $dispensedAt,
                        performedBy: $performedBy,
                        referenceType: Dispensation::class,
                        referenceId: $dispensation->getKey(),
                        notes: Arr::get($req, 'notes'),
                    );

                    $allocated[] = [
                        'drug_id' => $drugId,
                        'drug_batch_id' => $batch->getKey(),
                        'quantity' => $take,
                        'unit_price' => $unitPrice,
                        'line_total' => $unitPrice * $take,
                        'notes' => Arr::get($req, 'notes'),
                    ];

                    $remaining -= $take;
                }

                if ($remaining > 0) {
                    // Force failure so the transaction rolls back.
                    throw new \RuntimeException('Insufficient stock to complete dispensation.');
                }
            }

            foreach ($allocated as $row) {
                DispensationItem::create([
                    'dispensation_id' => $dispensation->getKey(),
                    'drug_id' => $row['drug_id'],
                    'drug_batch_id' => $row['drug_batch_id'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'line_total' => $row['line_total'],
                    'notes' => $row['notes'],
                ]);
            }

            $dispensation->status = 'completed';
            $dispensation->dispensed_by = $performedBy;
            $dispensation->dispensed_at = $dispensedAt;
            $dispensation->save();

            return $dispensation->refresh()->load('items');
        }, 3);
    }

    private function deductFromBatch(
        DrugBatch $batch,
        int $quantity,
        \DateTimeInterface $occurredAt,
        ?string $performedBy,
        string $referenceType,
        string $referenceId,
        ?string $notes,
    ): void {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Deduct quantity must be greater than zero.');
        }

        $batch->quantity_on_hand = (int) $batch->quantity_on_hand - $quantity;

        if ((int) $batch->quantity_on_hand < 0) {
            throw new \RuntimeException('Stock would become negative.');
        }

        $batch->save();

        StockMovement::create([
            'drug_id' => $batch->drug_id,
            'drug_batch_id' => $batch->getKey(),
            'type' => 'dispense',
            'quantity' => -1 * $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by' => $performedBy,
            'occurred_at' => $occurredAt,
            'notes' => $notes,
        ]);
    }
}
