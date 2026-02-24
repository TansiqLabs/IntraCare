<?php

declare(strict_types=1);

namespace App\Filament\Resources\DrugBatchResource\Pages;

use App\Filament\Resources\DrugBatchResource;
use App\Models\StockMovement;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDrugBatch extends EditRecord
{
    protected static string $resource = DrugBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! StockMovement::query()
                    ->where('drug_batch_id', $this->record->getKey())
                    ->exists()
                )
                ->before(function () {
                    abort_if(
                        StockMovement::query()
                            ->where('drug_batch_id', $this->record->getKey())
                            ->exists(),
                        403,
                        __('Cannot delete a batch that has stock movements. Deactivate it instead.')
                    );
                }),
        ];
    }
}
