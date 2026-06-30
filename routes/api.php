<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AssetlyQrController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| File ini memuat rute utama untuk API SILAP.
| Karena kamu memakai sistem route modular, file ini
| berfungsi sebagai rute global — tetap bisa memuat
| route tambahan dari modules/api.php (atau lainnya).
|
| Semua route otomatis diberi middleware "api" oleh RouteServiceProvider.
|
*/

Route::get('/check', function () {
  return response()->json(['message' => 'SILAP API aktif']);
});

Route::post('assetly/qr/resolve', [AssetlyQrController::class, 'resolve'])
  ->middleware('assetly.key');
  
// ======================================================
// =============== API VERSION 1 =========================
// ======================================================
Route::prefix('v1')->group(function () {
  Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api');

  Route::middleware(['protected_api', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/config/presensi', [ConfigController::class, 'presensi']);
    
    // Presensi bisa pakai limiter khusus yang lebih ketat (opsional)
    // Route::post('/attendance', [AttendanceController::class, 'store'])
    // ->middleware('throttle:attendance');

    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);

    Route::post('/attendance/override', [AttendanceController::class, 'override'])
      ->middleware(['checkRole:guru_piket,kesiswaan,admin,guru', 'throttle:api']);
  });
});
