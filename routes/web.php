<?php

use App\Http\Controllers\SetupController;
use App\Models\QueueDepartment;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Setup Wizard (runs only when no admin user exists)
|--------------------------------------------------------------------------
*/
Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

/*
|--------------------------------------------------------------------------
| Queue Display (public, LAN)
|--------------------------------------------------------------------------
*/
Route::get('/queue/display/{department}', function (QueueDepartment $department) {
    abort_unless($department->is_active, 404);

    return view('queue.display', [
        'department' => $department,
    ]);
})->name('queue.display');

/*
|--------------------------------------------------------------------------
| Root redirect — sends to admin panel
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect('/admin');
});
