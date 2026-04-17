<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\QrToken;
use App\Models\Siswa;
use App\Models\Guru;

class AssetlyQrController extends Controller {

  public function resolve(Request $request) {
    $data = $request->validate([
      'token' => ['required', 'string'],
    ]);

    $token = QrToken::query()
      ->where('token', $data['token'])
      ->where('purpose', 'assetly_borrower')
      ->first();

    if (!$token) {
      return response()->json(['ok' => false, 'error' => 'TOKEN_NOT_FOUND'], 404);
    }

    if ($token->isExpired()) {
      return response()->json(['ok' => false, 'error' => 'TOKEN_EXPIRED'], 404);
    }

    // token single-use: kalau sudah dipakai, tolak
    if ($token->isUsed()) {
      return response()->json(['ok' => false, 'error' => 'TOKEN_USED'], 409);
    }

    if ($token->subject_type === 'student') {
      $siswa = Siswa::with('classroom')
        ->where('nis', $token->subject_ref)
        ->first();

      if (!$siswa) {
        return response()->json(['ok' => false, 'error' => 'STUDENT_NOT_FOUND'], 404);
      }

      // mark used
      $token->forceFill([
        'used_at' => now(),
        'used_count' => $token->used_count + 1,
      ])->save();

      return response()->json([
        'ok' => true,
        'type' => 'borrower',
        'role' => 'student',
        'ref'  => $siswa->nis,
        'nis'  => $siswa->nis,
        'nama_lengkap' => $siswa->nama_lengkap,
        'kelas' => optional($siswa->classroom)->name,
        'kontak' => $siswa->kontak,
        'exp' => $token->expires_at->timestamp,
      ]);
    }

    if ($token->subject_type === 'teacher') {
      $guru = Guru::where('nip', $token->subject_ref)->first();

      if (!$guru) {
        return response()->json(['ok' => false, 'error' => 'TEACHER_NOT_FOUND'], 404);
      }

      // mark used
      $token->forceFill([
        'used_at' => now(),
        'used_count' => $token->used_count + 1,
      ])->save();

      return response()->json([
        'ok' => true,
        'type' => 'borrower',
        'role' => 'teacher',
        'ref'  => $guru->nip,
        'nip'  => $guru->nip,
        'nama_lengkap' => $guru->nama_lengkap,
        'kontak' => $guru->kontak,
        'exp' => $token->expires_at->timestamp,
      ]);
    }

    return response()->json(['ok' => false, 'error' => 'TOKEN_UNKNOWN_TYPE'], 400);
  }
}