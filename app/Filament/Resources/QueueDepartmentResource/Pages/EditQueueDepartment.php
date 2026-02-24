<?php

declare(strict_types=1);

namespace App\Filament\Resources\QueueDepartmentResource\Pages;

use App\Filament\Resources\QueueDepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQueueDepartment extends EditRecord
{
    protected static string $resource = QueueDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
