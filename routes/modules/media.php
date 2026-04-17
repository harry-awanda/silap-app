<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MediaController;

Route::middleware('web') // publik; hapus 'auth' supaya bisa di-embed <img>
  ->get('/media/{path}', [MediaController::class, 'show'])
  ->where('path', '.*')
  ->name('media');
