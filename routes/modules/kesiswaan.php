<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Kesiswaan\OutPassReadController;

/*
|-----------------------------------------------------------------------
| Modul: Kesiswaan
| Prefix URL: /kesiswaan
| Prefix route name: kesiswaan.*
| Akses: role kesiswaan
|-----------------------------------------------------------------------
*/

Route::prefix('kesiswaan')->name('kesiswaan.')->middleware('checkRole:kesiswaan')->group(function () {
  
  Route::get('izin/keluar',     [OutPassReadController::class, 'index'])->name('outpasses.index');
  Route::get('izin/keluar/dt',  [OutPassReadController::class, 'dt'])->name('outpasses.dt');
});