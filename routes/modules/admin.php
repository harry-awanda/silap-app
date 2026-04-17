<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
  RoleController,
  AcademicTermController,
  GuruController,
  HomeroomAssignmentController,
  SiswaController as AdminSiswaController,
  ClassroomController,
  JadwalPiketController,
  PelanggaranController,
  ProfilSekolahController,
  UploadController,
  UserController,
  SiswaUserController,
  UserPasswordController,
  QrTokenController,
};

Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {

  // Reset password sementara
  Route::post('users/{user}/reset-password-temp', [UserPasswordController::class, 'resetTemp'])
    ->name('users.reset-password.temp');

  // Resources
  Route::resource('terms', AcademicTermController::class)->except(['show']);
  // Set Active (PATCH lebih semantik)
  Route::patch('terms/{term}/activate', [AcademicTermController::class, 'activate'])
  ->name('terms.activate');
  
  Route::get('homeroom-assignments', [HomeroomAssignmentController::class, 'index'])->name('homeroom.index');
  Route::get('homeroom-assignments/create', [HomeroomAssignmentController::class, 'create'])->name('homeroom.create');
  Route::post('homeroom-assignments', [HomeroomAssignmentController::class, 'store'])->name('homeroom.store');
  Route::get('homeroom/{homeroom}/edit', [HomeroomAssignmentController::class, 'edit'])
    ->name('homeroom.edit');
  Route::put('homeroom/{homeroom}', [HomeroomAssignmentController::class, 'update'])
    ->name('homeroom.update');
    Route::delete('homeroom-assignments/{homeroom}', [HomeroomAssignmentController::class, 'destroy'])->name('homeroom.destroy');
    Route::post('homeroom-assignments/{homeroom}/end', [HomeroomAssignmentController::class, 'end'])->name('homeroom.end');
  
  Route::get('/classrooms/clone', [ClassroomController::class, 'cloneForm'])->name('classrooms.clone.form');
  Route::post('/classrooms/clone', [ClassroomController::class, 'cloneCommit'])->name('classrooms.clone.commit');

  Route::resources([
    'roles'          => RoleController::class,
    'guru'          => GuruController::class,
    'classrooms'    => ClassroomController::class,
    'siswa'         => AdminSiswaController::class,
    'jadwal-piket'  => JadwalPiketController::class,
    'pelanggaran'   => PelanggaranController::class,
    'uploads'       => UploadController::class,
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

  // Endpoints khusus
  Route::get('siswa-data', [AdminSiswaController::class, 'getData'])->name('siswa.data');
  Route::post('guru/import', [GuruController::class, 'import'])->name('guru.import');
  Route::post('pelanggaran/import', [PelanggaranController::class, 'importExcel'])->name('pelanggaran.import');

  // Profil sekolah
  Route::controller(ProfilSekolahController::class)->group(function () {
    Route::get('profil', 'edit')->name('profil.edit');
    Route::put('profil', 'update')->name('profil.update');
  });

  // Uploads download
  Route::get('uploads/download/{upload}', [UploadController::class, 'download'])->name('uploads.download');

  Route::prefix('qr-tokens')->name('qr-tokens.')->group(function () {
    Route::get('/', [QrTokenController::class, 'index'])->name('index');
    Route::post('/cleanup', [QrTokenController::class, 'cleanup'])->name('cleanup');
  });
});