<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Classroom, Siswa};
use Illuminate\Support\Str;

class ClassroomStudentsController extends Controller {
  public function index(Request $request, Classroom $classroom) {
    // Guard akses: hanya guru_piket, kesiswaan, guru (wali)
    $role = strtolower(str_replace(['-', ' '], '_', auth()->user()->role ?? ''));

    if (!in_array($role, ['guru_piket','kesiswaan','guru'], true)) {
      abort(403, 'Unauthorized.');
    }

    // Jika role = guru (wali), pastikan hanya bisa akses kelas binaannya
    if ($role === 'guru') {
      $guru = auth()->user()->guru;
      // Sesuaikan relasi kamu: classrooms() atau classroom_id tunggal
      $allowed = method_exists($guru, 'classrooms')
        ? $guru->classrooms()->pluck('id')->all()
        : (array)($guru?->classroom_id ? [$guru->classroom_id] : []);
      if (!in_array($classroom->id, $allowed, true)) {
        abort(403, 'Bukan kelas binaan.');
      }
    }

    // Optional filter pencarian nama (q=)
    $q = trim((string)$request->query('q', ''));

    $students = Siswa::select('id','nama_lengkap')
      ->where('classroom_id', $classroom->id)
      ->when($q !== '', fn($qq) => $qq->where('nama_lengkap', 'like', "%{$q}%"))
      ->orderBy('nama_lengkap')
      ->get();

    return response()->json($students);
  }
}