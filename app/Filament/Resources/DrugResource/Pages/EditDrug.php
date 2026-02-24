<?php

declare(strict_types=1);

namespace App\Filament\Resources\DrugResource\Pages;

use App\Filament\Resources\DrugResource;
use App\Models\DrugBatch;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDrug extends EditRecord
{
    protected static string $resource = DrugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! DrugBatch::query()
                    ->where('drug_id', $this->record->getKey())
                    ->exists()
                )
                ->before(function () {
                    abort_if(
                        DrugBatch::query()
                            ->where('drug_id', $this->record->getKey())
                            ->exists(),
                        403,
                        'Cannot delete a drug that has batches. Deactivate it instead.'
                    );
                }),
        ];
    }
}
