<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

Route::prefix('profile')->name('profile.')->group(function () {
  Route::get('edit',   [ProfileController::class, 'edit'])->name('edit');
  Route::put('update', [ProfileController::class, 'update'])->name('update');

  // ✅ Hanya non-siswa yang boleh update foto
  Route::post('update-photo', [ProfileController::class, 'updatePhoto'])
    ->middleware('role:admin|guru|guru_bk|wali_kelas|guru_piket|guru_bk|kesiswaan')
    ->name('updatePhoto');

  Route::put('update-password', [ProfileController::class, 'updatePassword'])->name('updatePassword');
});
