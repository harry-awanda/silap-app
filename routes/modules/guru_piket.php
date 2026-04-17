<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgendaPiketController;

// Rute untuk role: guru_piket
Route::middleware('role:guru_piket')->group(function () {
  Route::resource('agenda_piket', AgendaPiketController::class);
  Route::get('agenda_piket/export/{id}', [AgendaPiketController::class, 'exportPdf'])->name('agenda_piket.export');

});
