<?php

declare(strict_types=1);

namespace App\Filament\Resources\DispensationResource\Pages;

use App\Enums\DispensationStatus;
use App\Filament\Resources\DispensationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDispensation extends CreateRecord
{
    protected static string $resource = DispensationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = DispensationStatus::Draft;
        $data['dispensed_at'] = null;
        $data['dispensed_by'] = null;

        return $data;
    }
}
