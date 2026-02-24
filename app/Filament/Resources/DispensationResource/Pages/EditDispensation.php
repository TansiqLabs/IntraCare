<?php

declare(strict_types=1);

namespace App\Filament\Resources\DispensationResource\Pages;

use App\Enums\DispensationStatus;
use App\Filament\Resources\DispensationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDispensation extends EditRecord
{
    protected static string $resource = DispensationResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        // Only draft dispensations may be edited.
        abort_unless(
            $this->record->status === DispensationStatus::Draft,
            403,
            __('Only draft dispensations can be edited.')
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === DispensationStatus::Draft),
        ];
    }
}
