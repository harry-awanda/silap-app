<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\QrToken;
use App\Models\Siswa;
use App\Models\Guru;

class MyQrController extends Controller {

  private int $ttlMinutes = 5;

  public function show(Request $request) {
    
    $title = 'My QR Code for Assetly Borrowing';
    $user = $request->user();

    // 1) Deteksi borrower (student/teacher)
    [$subjectType, $subjectRef, $display] = $this->resolveBorrowerFromUser($user->id);

    // 2) Cari token aktif (belum expired & belum used)
    $token = QrToken::query()
      ->where('purpose', 'assetly_borrower')
      ->where('subject_type', $subjectType)
      ->where('subject_ref', $subjectRef)
      ->whereNull('used_at')
      ->where('expires_at', '>', now())
      ->orderByDesc('id')
      ->first();

    // 3) Jika tidak ada token aktif → buat token baru
    if (!$token) {
      $token = $this->createToken($user->id, $subjectType, $subjectRef, $display);
    }

    return view('my_qr.show', [
      'title' => $title,
      'token' => $token,
      'display' => $display,
      'ttlMinutes' => $this->ttlMinutes,
    ]);
  }

  public function regenerate(Request $request) {
    $user = $request->user();

    [$subjectType, $subjectRef, $display] = $this->resolveBorrowerFromUser($user->id);

    // Nonaktifkan token aktif sebelumnya (biar 1 user cuma punya 1 token aktif)
    QrToken::query()
      ->where('purpose', 'assetly_borrower')
      ->where('subject_type', $subjectType)
      ->where('subject_ref', $subjectRef)
      ->whereNull('used_at')
      ->where('expires_at', '>', now())
      ->update(['expires_at' => now()]);

    // Buat token baru
    $this->createToken($user->id, $subjectType, $subjectRef, $display);

    return redirect()->route('my-qr.show')->with('success', 'QR berhasil dibuat ulang.');
  }

  private function createToken(?int $createdBy, string $subjectType, string $subjectRef, array $display): QrToken {
    return QrToken::create([
      'token' => Str::random(48),
      'purpose' => 'assetly_borrower',
      'subject_type' => $subjectType, // student|teacher
      'subject_ref' => $subjectRef,   // nis|nip
      'payload' => [
        // optional: data ringkas untuk debug/audit
        'name' => $display['name'] ?? null,
        'org'  => $display['org'] ?? null,
      ],
      'expires_at' => now()->addMinutes($this->ttlMinutes),
      'created_by' => $createdBy,
    ]);
  }

  /**
   * Return: [$subjectType, $subjectRef, $display]
   * $display untuk ditampilkan di UI.
   */
  private function resolveBorrowerFromUser(int $userId): array {
    // coba siswa dulu
    $siswa = Siswa::with('classroom')->where('user_id', $userId)->first();
    if ($siswa) {
      return [
        'student',
        (string) $siswa->nis,
        [
          'role' => 'Siswa',
          'name' => $siswa->nama_lengkap,
          'ref'  => $siswa->nis,
          'org'  => optional($siswa->classroom)->name, // kelas
        ],
      ];
    }

    // lalu guru
    $guru = Guru::where('user_id', $userId)->first();
    if ($guru) {
      return [
        'teacher',
        (string) $guru->nip,
        [
          'role' => 'Guru',
          'name' => $guru->nama_lengkap,
          'ref'  => $guru->nip,
          'org'  => 'Guru',
        ],
      ];
    }

    abort(403, 'Akun ini bukan siswa/guru (tidak punya data borrower).');
  }
}