<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrivacyController;

// Redirect root
Route::redirect('/', '/auth/login');

// Muat rute non-protected
require __DIR__ . '/modules/auth.php';
require __DIR__ . '/modules/media.php';
Route::get('privacy', [PrivacyController::class, 'index'])->name('privacy');

// Rute dengan middleware 'protected'
Route::middleware(['protected'])->group(function () {
  require __DIR__ . '/modules/dashboard.php';
  require __DIR__ . '/modules/my_qr.php';
  require __DIR__ . '/modules/profile.php';
  require __DIR__ . '/modules/admin.php';
  require __DIR__ . '/modules/audit_attendance.php';
  require __DIR__ . '/modules/wali_kelas.php';
  require __DIR__ . '/modules/guru_piket.php';
  require __DIR__ . '/modules/pelanggaran_share.php';
  require __DIR__ . '/modules/siswa.php';
  require __DIR__ . '/modules/kesiswaan.php';
  require __DIR__.'/modules/api.php';
});