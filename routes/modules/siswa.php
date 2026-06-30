<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Siswa\SelfAttendanceController;

Route::middleware(['protected', 'role:siswa'])->group(function () {

  // =========================
  // PRESENSI MANDIRI
  // =========================
  Route::get('presensi', [SelfAttendanceController::class, 'form'])
    ->name('presensi.form');

  Route::post('presensi/precheck', [SelfAttendanceController::class, 'precheck'])
    ->name('presensi.precheck');

  Route::post('presensi', [SelfAttendanceController::class, 'store'])
    ->middleware(['throttle:attendance'])
    ->name('presensi.store');

  Route::get('presensi/terlambat/{token}', [SelfAttendanceController::class, 'lateForm'])
    ->where('token', '[^/]+')
    ->name('presensi.late.form');

  Route::post('presensi/terlambat/{token}', [SelfAttendanceController::class, 'lateStore'])
    ->where('token', '[^/]+')
    ->middleware(['throttle:attendance'])
    ->name('presensi.late.store');
});