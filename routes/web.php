<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\OperationHistoryController;
use App\Http\Controllers\OperatorTokenController;
use App\Http\Controllers\PishockController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;



Route::redirect('/dashboard', '/')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/', [PishockController::class, 'index']);
    Route::get('/pishock', [PishockController::class, 'index'])->name('pishock');
    Route::post('/pishock', [PishockController::class, 'sendCommand'])->middleware('throttle:commands');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/updateMaxValues', [PishockController::class, 'updateMaxValues'])->name('updateMaxValues');

    Route::resource('devices', DeviceController::class);

    Route::get('/operators', [OperatorTokenController::class, 'index'])->name('operators.index');
    Route::post('/operators', [OperatorTokenController::class, 'store'])->name('operators.store');
    Route::delete('/operators/{operatorToken}', [OperatorTokenController::class, 'destroy'])->name('operators.destroy');
    Route::post('/operators/prune', [OperatorTokenController::class, 'prune'])->name('operators.prune');

    Route::get('/history', [OperationHistoryController::class, 'index'])->name('history.index');
    Route::post('/history/prune', [OperationHistoryController::class, 'prune'])->name('history.prune');
});

// Named operator links: no login required, scoped to a single revocable token.
Route::get('/pishock/{token}', [PishockController::class, 'operate'])->name('pishock.operate');
Route::post('/pishock/{token}', [PishockController::class, 'sendCommandAs'])
    ->middleware('throttle:commands')
    ->name('pishock.operate.send');

// Polled by the control panel so operators see a max-value change without reloading.
Route::get('/max-values', [PishockController::class, 'maxValues'])->name('maxValues');


require __DIR__.'/auth.php';
