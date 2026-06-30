<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
  AcademicTermController,
  GuruController,
  HomeroomAssignmentController,
  SiswaController as AdminSiswaController,
  ClassroomController,
  PelanggaranController,
  ProfilSekolahController,
  UploadController
};

Route::prefix('admin')->name('admin.')->middleware('role:superadmin')->group(function () {

  Route::resource('terms', AcademicTermController::class)->except(['show']);
  Route::patch('terms/{term}/activate', [AcademicTermController::class, 'activate'])
    ->name('terms.activate');
  
  Route::get('homeroom-assignments', [HomeroomAssignmentController::class, 'index'])->name('homeroom.index');
  Route::get('homeroom-assignments/create', [HomeroomAssignmentController::class, 'create'])->name('homeroom.create');
  Route::post('homeroom-assignments', [HomeroomAssignmentController::class, 'store'])->name('homeroom.store');
  Route::get('homeroom/{homeroom}/edit', [HomeroomAssignmentController::class, 'edit'])->name('homeroom.edit');
  Route::put('homeroom/{homeroom}', [HomeroomAssignmentController::class, 'update'])->name('homeroom.update');
  Route::delete('homeroom-assignments/{homeroom}', [HomeroomAssignmentController::class, 'destroy'])->name('homeroom.destroy');
  Route::post('homeroom-assignments/{homeroom}/end', [HomeroomAssignmentController::class, 'end'])->name('homeroom.end');
  
  Route::get('/classrooms/clone', [ClassroomController::class, 'cloneForm'])->name('classrooms.clone.form');
  Route::post('/classrooms/clone', [ClassroomController::class, 'cloneCommit'])->name('classrooms.clone.commit');

  Route::resources([
    'guru'        => GuruController::class,
    'classrooms'  => ClassroomController::class,
    'siswa'       => AdminSiswaController::class,
    'pelanggaran' => PelanggaranController::class,
    'uploads'     => UploadController::class,
  ]);

  Route::post('guru/import', [GuruController::class, 'import'])->name('guru.import');
  Route::get('siswa-data', [AdminSiswaController::class, 'getData'])->name('siswa.data');
  Route::post('pelanggaran/import', [PelanggaranController::class, 'importExcel'])->name('pelanggaran.import');

  Route::controller(ProfilSekolahController::class)->group(function () {
    Route::get('profil', 'edit')->name('profil.edit');
    Route::put('profil', 'update')->name('profil.update');
  });

  Route::get('uploads/download/{upload}', [UploadController::class, 'download'])->name('uploads.download');
});
