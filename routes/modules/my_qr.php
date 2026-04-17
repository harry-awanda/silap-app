<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MyQrController;

Route::get('/my-qr', [MyQrController::class, 'show'])->name('my-qr.show');
Route::post('/my-qr/regenerate', [MyQrController::class, 'regenerate'])->name('my-qr.regenerate');