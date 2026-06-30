<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssetlyQrController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Route API yang masih aktif dipakai oleh SILAP.
| Endpoint mobile `/api/v1/*` sudah dinonaktifkan karena aplikasi mobile
| tidak dilanjutkan.
|
*/

Route::get('/check', function () {
  return response()->json(['message' => 'SILAP API aktif']);
});

Route::post('assetly/qr/resolve', [AssetlyQrController::class, 'resolve'])
  ->middleware('assetly.key');