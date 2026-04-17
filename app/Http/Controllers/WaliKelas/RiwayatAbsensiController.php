<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Siswa, Attendance, HomeroomAssignment};
use App\Support\HomeroomContext;

class RiwayatAbsensiController extends Controller {
  /**
   * Ambil homeroom dari request + termId/classroomId
   */
  private function homeroomContext(Request $request): array {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');

    abort_if(!$homeroom || !$homeroom->classroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $termId      = (int) $homeroom->term_id;
    $classroomId = (int) $homeroom->classroom->id;

    return [$homeroom, $termId, $classroomId];
  }

  public function index(Request $request, Siswa $siswa) {
    [$homeroom, $termId, $classroomId] = $this->homeroomContext($request);

    // ✅ OTORISASI TERM-AWARE (single source of truth)
    HomeroomContext::assertSiswaBinaanTerm($termId, $classroomId, (int) $siswa->id);

    $from = $request->query('from');
    $to   = $request->query('to');

    // (opsional) validasi format tanggal kalau diisi
    // biar tidak error jika user input random string
    if ($from || $to) {
      $request->validate([
        'from' => ['nullable', 'date_format:Y-m-d'],
        'to'   => ['nullable', 'date_format:Y-m-d'],
      ]);
    }

    $items = Attendance::query()
      ->where('term_id', $termId)              // ✅ kunci term
      ->where('classroom_id', $classroomId)    // ✅ kunci kelas per term
      ->where('siswa_id', (int) $siswa->id)
      ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
      ->when($to,   fn($q) => $q->whereDate('date', '<=', $to))
      ->orderByDesc('date')
      ->orderByDesc('time')
      ->get();

    $rekap = [
      'hadir'     => $items->where('status', 'hadir')->count(),
      'izin'      => $items->where('status', 'izin')->count(),
      'sakit'     => $items->where('status', 'sakit')->count(),
      'alpa'      => $items->where('status', 'alpa')->count(),
      'terlambat' => $items->where('status', 'terlambat')->count(),
    ];

    $title = 'Riwayat Absensi Siswa';

    return view('wali-kelas.siswa.riwayat-absensi', compact(
      'title', 'siswa', 'items', 'rekap', 'from', 'to'
    ));
  }
}