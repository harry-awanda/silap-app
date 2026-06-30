<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
  RoleController,
  JadwalPiketController,
  UserController,
  SiswaUserController,
  SiswaPromotionController,
  UserPasswordController,
  QrTokenController
};

Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {

  // Reset password sementara
  Route::post('users/{user}/reset-password-temp', [UserPasswordController::class, 'resetTemp'])
    ->name('users.reset-password.temp');

  Route::prefix('siswa')->name('siswa.')->group(function () {
    Route::get('pindah-kelas', fn() => redirect()->route('admin.siswa.promosi.index', 'promote'))->name('move.index');
    Route::get('kelulusan', fn() => redirect()->route('admin.siswa.promosi.index', 'graduate'))->name('graduate.index');
    Route::get('promosi/{mode}',          [SiswaPromotionController::class, 'index'])->name('promosi.index');
    Route::post('promosi/{mode}/preview', [SiswaPromotionController::class, 'preview'])->name('promosi.preview');
    Route::post('promosi/{mode}/commit',  [SiswaPromotionController::class, 'commit'])->name('promosi.commit');
  });

  Route::resources([
    'roles'          => RoleController::class,
    'jadwal-piket'  => JadwalPiketController::class,
    'users'         => UserController::class,
  ]);

  // Manajemen user-siswa (khusus)
  Route::prefix('user-siswa')->name('user-siswa.')->group(function () {
    // AJAX untuk Yajra DataTables
    Route::get('datatable', [SiswaUserController::class, 'datatable'])->name('datatable');
    // ✅ EXPORT Excel (mengikuti filter)
    Route::get('export', [SiswaUserController::class, 'export'])->name('export');
    Route::resource('/', SiswaUserController::class)
      ->parameters(['' => 'siswa'])
      ->except(['show']);
  });

  Route::prefix('qr-tokens')->name('qr-tokens.')->group(function () {
    Route::get('/', [QrTokenController::class, 'index'])->name('index');
    Route::post('late/generate', [QrTokenController::class, 'generateLateQr'])->name('late.generate');
    Route::post('cleanup', [QrTokenController::class, 'cleanup'])->name('cleanup');
  });
  
});
