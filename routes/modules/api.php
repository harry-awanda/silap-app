<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClassroomStudentsController;

// Endpoint JSON: daftar siswa per kelas
Route::prefix('api')->name('api.')->group(function () {
  // Pakai guard di controller agar hanya role tertentu yang boleh akses
  Route::get('classrooms/{classroom}/students', [ClassroomStudentsController::class, 'index'])
  ->name('classrooms.students'); // GET /api/classrooms/13/students
});