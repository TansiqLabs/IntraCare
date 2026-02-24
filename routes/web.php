<?php

use App\Http\Controllers\SetupController;
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
| Root redirect — sends to admin panel
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect('/admin');
});
