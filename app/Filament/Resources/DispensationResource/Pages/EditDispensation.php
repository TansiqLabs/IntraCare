<?php

declare(strict_types=1);

namespace App\Filament\Resources\DispensationResource\Pages;

use App\Filament\Resources\DispensationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDispensation extends EditRecord
{
    protected static string $resource = DispensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
