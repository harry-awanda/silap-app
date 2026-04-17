<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PelanggaranSiswaController;

// Shared antara admin, guru_bk, kesiswaan, dan wali_kelas (via sync role)
Route::middleware(['auth', 'role:admin|kesiswaan|wali_kelas|guru_bk'])->group(function () {
  Route::get('pelanggaran-siswa/datatable', [PelanggaranSiswaController::class, 'datatable'])
    ->name('pelanggaranSiswa.datatable');

  Route::resource('pelanggaranSiswa', PelanggaranSiswaController::class);

});
