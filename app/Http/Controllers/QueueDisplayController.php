<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QueueDepartment;

class QueueDisplayController extends Controller
{
    public function __invoke(QueueDepartment $department)
    {
        abort_unless($department->is_active, 404);

        return view('queue.display', [
            'department' => $department,
        ]);
    }
}
