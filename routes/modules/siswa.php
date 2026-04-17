<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Siswa\SelfAttendanceController;
use App\Http\Controllers\Siswa\FaceEnrollmentController;
use App\Http\Controllers\Siswa\FaceAttendanceController;

Route::middleware(['protected', 'role:siswa'])->group(function () {

  // =========================
  // PRESENSI (SELF / TRANSISI)
  // =========================
  Route::get('presensi', [SelfAttendanceController::class, 'form'])
    ->name('presensi.form');

  Route::post('presensi/precheck', [SelfAttendanceController::class, 'precheck'])
    ->name('presensi.precheck');

  Route::post('presensi', [SelfAttendanceController::class, 'store'])
    ->middleware(['throttle:attendance'])
    ->name('presensi.store');


  // =========================
  // HALAMAN UI PRESENSI WAJAH
  // =========================
  Route::get('face', function () {
    $title = "Presensi Wajah";
    return view('siswa.face', compact('title'));
  })->name('siswa.face.page');


  // =========================
  // FACE API (ENROLL + ATTEND)
  // =========================
  Route::prefix('face')->group(function () {

    Route::get('status', [FaceEnrollmentController::class, 'status'])
      ->name('siswa.face.status');

    Route::post('enroll/start', [FaceEnrollmentController::class, 'start'])
      ->middleware(['throttle:attendance'])
      ->name('siswa.face.enroll.start');

    Route::post('enroll/submit', [FaceEnrollmentController::class, 'submit'])
      ->middleware(['throttle:attendance'])
      ->name('siswa.face.enroll.submit');

    // ✅ INI yang tadi kurang / salah posisi
    Route::post('attendance', [FaceAttendanceController::class, 'store'])
      ->middleware(['throttle:attendance'])
      ->name('siswa.face.attendance.store');
  });
});