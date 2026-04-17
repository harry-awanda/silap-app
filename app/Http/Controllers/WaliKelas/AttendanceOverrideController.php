<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Support\HomeroomContext;
use App\Models\{Attendance, Siswa, HomeroomAssignment};

class AttendanceOverrideController extends Controller {
  /**
   * Helper: pastikan wali punya homeroom aktif
   */
  private function homeroomOrFail(Request $request): HomeroomAssignment {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');

    abort_if(
      !$homeroom || !$homeroom->classroom,
      403,
      'Anda bukan wali kelas pada term aktif atau belum memiliki kelas binaan.'
    );

    return $homeroom;
  }

  // === 1) Override dari record yang SUDAH ADA (terlambat → hadir) ===
  public function markPresent(Request $request, Attendance $attendance) {
    $homeroom    = $this->homeroomOrFail($request);
    $termId      = (int) $homeroom->term_id;
    $classroomId = (int) $homeroom->classroom->id;

    // Kunci ke kelas+term binaan
    abort_if(
      (int) $attendance->term_id !== $termId || (int) $attendance->classroom_id !== $classroomId,
      403,
      'Data presensi ini bukan dari kelas/term binaan Anda.'
    );

    // (Opsional tapi disarankan) Validasi term-aware via pivot untuk menghindari data kotor
    // Pakai '*' agar tetap lolos meskipun status pivot berubah (moved/inactive) tapi record presensi historis ada
    abort_unless(
      HomeroomContext::siswaInClassTerm($termId, $classroomId, (int) $attendance->siswa_id, '*'),
      403,
      'Siswa pada presensi ini bukan anggota kelas Anda pada term tersebut.'
    );

    $currentStatus = strtolower((string) $attendance->status);
    abort_if($currentStatus !== 'terlambat', 422, 'Status saat ini tidak bisa diubah menjadi HADIR.');

    $fromStatus = $attendance->status;

    $attendance->update(['status' => 'hadir']);

    $msg = 'Status diubah: ' . strtoupper($fromStatus) . ' → HADIR';

    if ($request->expectsJson()) {
      return response()->json(['ok' => true, 'message' => $msg]);
    }

    return back()->with('success', $msg);
  }

  // === 2) Override untuk "BELUM PRESENSI" (tidak ada record → buat HADIR) ===
  public function markPresentByStudent(Request $request) {
    $homeroom    = $this->homeroomOrFail($request);
    $termId      = (int) $homeroom->term_id;
    $classroomId = (int) $homeroom->classroom->id;

    $data = $request->validate([
      'siswa_id' => ['required', 'integer', 'exists:siswa,id'],
      'date'     => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
    ]);

    $siswaId = (int) $data['siswa_id'];
    $date    = $data['date'];

    // ✅ Pastikan siswa memang binaan pada TERM aktif (pivot term_classroom_siswa)
    HomeroomContext::assertSiswaBinaanTerm($termId, $classroomId, $siswaId);

    // Ambil siswa (untuk memastikan siswa ada + bisa dipakai jika nanti butuh data)
    $siswa = Siswa::query()->findOrFail($siswaId);

    // Cek apakah sudah ada presensi pada term & tanggal tsb
    $attendance = Attendance::query()
      ->where('term_id', $termId)
      ->where('classroom_id', $classroomId)
      ->where('siswa_id', $siswa->id)
      ->whereDate('date', $date)
      ->first();

    if ($attendance) {
      abort(409, 'Presensi hari ini sudah tercatat sebagai ' . $attendance->status . '.');
    }

    // Buat record baru sebagai HADIR
    $att = Attendance::create([
      'term_id'      => $termId,
      'siswa_id'     => $siswa->id,
      'classroom_id' => $classroomId,
      'date'         => $date,
      'time'         => config('presensi.default_time', '07:00:00'),
      'status'       => 'hadir',
      'source'       => 'wali_override',
      'user_agent'   => substr($request->userAgent() ?? '', 0, 255),
    ]);

    if ($request->expectsJson()) {
      return response()->json([
        'ok'      => true,
        'message' => 'Status diubah: BELUM PRESENSI → HADIR',
        'status'  => $att->status,
      ]);
    }

    return back()->with('success', 'Status diubah: BELUM PRESENSI → HADIR');
  }
}