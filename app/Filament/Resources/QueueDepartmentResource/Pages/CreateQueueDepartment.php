<?php

declare(strict_types=1);

namespace App\Filament\Resources\QueueDepartmentResource\Pages;

use App\Filament\Resources\QueueDepartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQueueDepartment extends CreateRecord
{
    protected static string $resource = QueueDepartmentResource::class;
}
