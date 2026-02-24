<?php

declare(strict_types=1);

namespace App\Filament\Resources\DrugBatchResource\Pages;

use App\Filament\Resources\DrugBatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDrugBatch extends CreateRecord
{
    protected static string $resource = DrugBatchResource::class;
}
