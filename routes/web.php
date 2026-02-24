<?php

use App\Http\Controllers\QueueDisplayController;
use App\Http\Controllers\SetupController;
use App\Models\QueueDepartment;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Setup Wizard (runs only when no admin user exists)
|--------------------------------------------------------------------------
*/
Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store')->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| Queue Display (public, LAN)
|--------------------------------------------------------------------------
*/
Route::get('/queue/display/{department}', QueueDisplayController::class)->name('queue.display');

/*
|--------------------------------------------------------------------------
| Root redirect — sends to admin panel
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/admin');
