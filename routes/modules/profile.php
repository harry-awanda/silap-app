<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

// Profil pengguna
Route::prefix('profile')->name('profile.')->group(function () {
  Route::get('edit',   [ProfileController::class, 'edit'])->name('edit');
  Route::put('update', [ProfileController::class, 'update'])->name('update');
  Route::post('update-photo',    [ProfileController::class, 'updatePhoto'])->name('updatePhoto');
  Route::put('update-password',  [ProfileController::class, 'updatePassword'])->name('updatePassword');
});
