<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\FirstChangePasswordController;

// Public auth routes
Route::controller(AuthController::class)->group(function () {
  Route::get('auth/login', 'showLoginForm')->name('login')->middleware('guest');
  Route::post('auth/login', 'postLogin')->name('postLogin');
  Route::post('logout', 'logout')->name('logout')->middleware('auth');
});

// Halaman ganti password pertama kali (cukup auth saja)
Route::middleware('auth')->group(function () {
  Route::get('password/first-change', [FirstChangePasswordController::class, 'create'])
    ->name('password.first.change');
  Route::post('password/first-change', [FirstChangePasswordController::class, 'store'])
    ->name('password.first.update');
});
