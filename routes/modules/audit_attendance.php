<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditAttendanceController;

/*
|--------------------------------------------------------------------------
| Modul Audit Presensi (Lintas Role)
|--------------------------------------------------------------------------
| Bisa diakses oleh: admin, kesiswaan, guru_piket, guru_bk.
| Route tetap dibatasi oleh middleware role.
*/

Route::prefix('audit/attendance')->name('audit.attendance.')->group(function () {
  Route::middleware('role:admin|kesiswaan|guru_piket|guru_bk')->group(function () {
    // Halaman utama audit presensi
    Route::get('/', [AuditAttendanceController::class, 'index'])->name('index');
    // Endpoint DataTables (tab Detail Aktivitas)
    Route::get('/dt', [AuditAttendanceController::class, 'dt'])->name('dt');
    // Export Excel
    Route::get('/export', [AuditAttendanceController::class, 'export'])->name('export');
    // Leaderboard siswa terlambat
    Route::get('/late', [AuditAttendanceController::class, 'lateLeaderboard'])->name('late');
  });
});
