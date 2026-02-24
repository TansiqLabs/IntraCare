<?php

declare(strict_types=1);

namespace App\Filament\Resources\DrugBatchResource\Pages;

use App\Filament\Resources\DrugBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDrugBatches extends ListRecords
{
    protected static string $resource = DrugBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
