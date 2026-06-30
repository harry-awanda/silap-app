<?php

use Illuminate\Support\Facades\Route;

/*
|-----------------------------------------------------------------------
| Modul: Kesiswaan
| Prefix URL: /kesiswaan
| Prefix route name: kesiswaan.*
| Akses: role kesiswaan
|-----------------------------------------------------------------------
*/

Route::prefix('kesiswaan')->name('kesiswaan.')->middleware('checkRole:kesiswaan')->group(function () {
});
