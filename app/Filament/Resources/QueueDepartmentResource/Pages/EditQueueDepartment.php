<?php

declare(strict_types=1);

namespace App\Filament\Resources\QueueDepartmentResource\Pages;

use App\Filament\Resources\QueueDepartmentResource;
use App\Models\QueueTicket;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQueueDepartment extends EditRecord
{
    protected static string $resource = QueueDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! QueueTicket::query()
                    ->where('queue_department_id', $this->record->getKey())
                    ->exists()
                )
                ->before(function () {
                    abort_if(
                        QueueTicket::query()
                            ->where('queue_department_id', $this->record->getKey())
                            ->exists(),
                        403,
                        'Cannot delete a department that has tickets. Deactivate it instead.'
                    );
                }),
        ];
    }
}
