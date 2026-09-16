<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\PishockController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;



Route::get('/dashboard', function () {
    return redirect('');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/updateMaxValues', [PishockController::class, 'updateMaxValues'])->name('updateMaxValues');
});

Route::get('/', [PishockController::class, 'index']);
Route::get('/pishock', [PishockController::class, 'index'])->name('pishock');
Route::post('/pishock', [PishockController::class, 'sendCommand']);

Route::resource('devices', DeviceController::class);


require __DIR__.'/auth.php';
