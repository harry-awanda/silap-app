<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\WaliKelas\{
  DashboardController,
  SiswaController as WaliKelasSiswaController,
  SiswaImportController,
  RiwayatPelanggaranController,
  RiwayatAbsensiController,
  SiswaHistoryController,
  MorningActivityAttendanceController,
  RekapController,
  AbsenceController,
  AttendanceOverrideController,
  AuditAttendanceController as WaliKelasAuditAttendanceController
};

Route::prefix('wali')
  ->middleware(['protected','syncWaliRole','role:wali_kelas'])
  ->group(function () {

    // =======================
    // DASHBOARD WALI (BARU)
    // =======================
    Route::get('/dashboard', [DashboardController::class, 'index'])
      ->middleware(['ensure.homeroom'])   // wajib wali aktif term berjalan
      ->name('wali.dashboard.index');     // pakai namespace nama 'wali.' agar jelas

    Route::prefix('riwayat-siswa')->name('wali.siswa-history.')->group(function () {
      Route::get('/', [SiswaHistoryController::class, 'index'])->name('index');
      Route::get('{assignment}/{siswa}', [SiswaHistoryController::class, 'show'])
        ->whereNumber(['assignment', 'siswa'])
        ->name('show');
    });

    // ===========================================================
    // RUTE-RUTE KRITIKAL (wajib wali aktif + injeksi konteks wali)
    // ===========================================================
    Route::middleware(['ensure.homeroom','inject.homeroom'])->group(function () {

        Route::prefix('siswa')->name('siswa.')->group(function () {
          Route::get('import',          [SiswaImportController::class, 'index'])->name('import');          // Step 1: form upload
          Route::post('import/preview', [SiswaImportController::class, 'preview'])->name('import.preview'); // Step 2: preview
          Route::post('import/commit',  [SiswaImportController::class, 'commit'])->name('import.commit');   // Step 3: commit
          // Template import
          Route::get('template-import', [SiswaImportController::class, 'downloadTemplate'])->name('template.download');
          
        });
      // Resource siswa (wali kelas)
      Route::resource('siswa', WaliKelasSiswaController::class)->whereNumber('siswa');

      // Sub-rute siswa (riwayat pelanggaran + AJAX + export PDF)
      Route::prefix('siswa/{siswa}')->name('siswa.')->group(function () {
        Route::get('riwayat-pelanggaran',       [RiwayatPelanggaranController::class, 'index'])->name('pelanggaran.index');
        Route::get('riwayat-pelanggaran/more',  [RiwayatPelanggaranController::class, 'more'])->name('pelanggaran.more');
        Route::get('riwayat-pelanggaran/export',[RiwayatPelanggaranController::class, 'export'])->name('pelanggaran.export');

        Route::get('riwayat-absensi',        [RiwayatAbsensiController::class, 'index'])->name('absensi.index');
        Route::get('riwayat-absensi/export', [RiwayatAbsensiController::class, 'export'])->name('absensi.export');

      });

      // ---------- Absence (wali kelas) ----------
      Route::resource('absence', AbsenceController::class)
        ->parameters(['absence' => 'attendance'])
        ->except(['show']);

      // ---------- Kegiatan Absensi Pagi ----------
      Route::prefix('kegiatan-absensi')->name('kegiatan-absensi.')->group(function () {
        Route::get('/',      [MorningActivityAttendanceController::class, 'index'])->name('index');
        Route::get('create', [MorningActivityAttendanceController::class, 'create'])->name('create');
        Route::post('/',     [MorningActivityAttendanceController::class, 'store'])->name('store');
      });

      // ---------- Rekap Bulanan ----------
      Route::get('rekap-bulanan',        [RekapController::class, 'monthlyRecap'])->name('monthlyRecap');
      Route::get('rekap-bulanan/export', [RekapController::class, 'exportMonthlyRecap'])->name('monthlyRecap.export');

      // ---------- Audit Presensi (Wali Kelas) ----------
      Route::prefix('audit/attendance')->name('wali.audit.attendance.')->group(function () {
        Route::get('/',      [WaliKelasAuditAttendanceController::class, 'index'])->name('index');
        Route::get('data',   [WaliKelasAuditAttendanceController::class, 'data'])->name('data');
        Route::get('export', [WaliKelasAuditAttendanceController::class, 'export'])->name('export');

        // Override status: TERLAMBAT -> HADIR
        Route::patch('{attendance}/mark-present', [AttendanceOverrideController::class, 'markPresent'])
        ->name('mark-present');

        // Override "belum presensi" (tidak ada record -> buat hadir)
        Route::post('mark-present-by-student', [AttendanceOverrideController::class, 'markPresentByStudent'])
        ->name('mark-present-by-student');
      });

      // ---------- Unduhan file upload (wali kelas) ----------
      Route::get('uploads/{upload}/download', [UploadController::class, 'download'])->name('uploads.download');
    });

  });
