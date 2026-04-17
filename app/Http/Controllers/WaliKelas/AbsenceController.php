<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Models\{Siswa, Attendance, HomeroomAssignment};
use App\Support\HomeroomContext;

class AbsenceController extends Controller {
  /** GET /wali-kelas/absence */
  public function index(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $classroom = $homeroom->classroom;
    $termId    = (int) $homeroom->term_id;
    $date      = $this->resolveDate($request);
    $title     = "Absensi Siswa";

    $absences = Attendance::query()
      ->with('siswa:id,nis,nama_lengkap,classroom_id')
      ->where('term_id', $termId)                  // kunci ke term aktif
      ->whereDate('date', $date)
      ->where('classroom_id', (int) $classroom->id)
      ->whereIn('status', ['sakit', 'izin', 'alpa'])
      ->orderByDesc('id')
      ->get();

    return view('wali-kelas.absence.index', compact('title', 'classroom', 'absences', 'date'));
  }

  /** GET /wali-kelas/absence/create */
  public function create(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $classroom = $homeroom->classroom;
    $termId    = (int) $homeroom->term_id;
    $date      = $this->resolveDate($request);
    $title     = "Input Ketidakhadiran - {$classroom->nama_kelas}";

    // Ambil siswa binaan TERM aktif (pivot term_classroom_siswa)
    $siswaIds = HomeroomContext::siswaIdsInClassTerm($termId, (int) $classroom->id, 'active');

    $siswa = Siswa::query()
      ->whereIn('id', $siswaIds)
      ->orderBy('nama_lengkap')
      ->get(['id', 'nis', 'nama_lengkap', 'classroom_id']);

    // Data absensi di tanggal tsb, untuk prefill di form
    $absences = Attendance::query()
      ->where('term_id', $termId)                 // kunci ke term aktif
      ->where('classroom_id', (int) $classroom->id)
      ->whereDate('date', $date)
      ->get()
      ->keyBy('siswa_id');

    return view('wali-kelas.absence.create', compact('title', 'siswa', 'absences', 'date'));
  }

  /** POST /wali-kelas/absence */
  public function store(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $classroom = $homeroom->classroom;
    $termId    = (int) $homeroom->term_id;

    // Ambil siswa binaan TERM aktif (pivot)
    $siswaIds = HomeroomContext::siswaIdsInClassTerm($termId, (int) $classroom->id, 'active');

    // Untuk cek cepat membership tanpa query berulang
    $siswaIdSet = array_flip($siswaIds->all());

    // baca config override guru
    $allowOverrideGuru = (bool) config('presensi.allow_override_guru_status');

    // Validasi tanggal & payload (longgarkan siswa_id; fallback ke key array)
    $request->merge(['date' => $this->resolveDate($request)]);
    $request->validate([
      'date'               => 'required|date_format:Y-m-d|before_or_equal:today',
      'absence'            => 'required|array',
      'absence.*.siswa_id' => 'nullable|integer',
      'absence.*.status'   => 'nullable|in:sakit,izin,alpa',
    ]);

    $date    = $request->input('date');
    $nowTime = now()->format('H:i:s');
    $ua      = substr($request->userAgent() ?? '', 0, 255);
    $payload = $request->input('absence', []);

    DB::transaction(function () use (
      $allowOverrideGuru,
      $payload,
      $date,
      $nowTime,
      $ua,
      $classroom,
      $termId,
      $siswaIdSet
    ) {
      foreach ($payload as $sidKey => $row) {
        $siswaId = (int) ($row['siswa_id'] ?? $sidKey);
        $status  = $row['status'] ?? null;

        if (!$status) continue;

        // Pastikan siswa termasuk siswa binaan pada TERM aktif
        if (!isset($siswaIdSet[$siswaId])) continue;

        $existing = Attendance::query()
          ->where('term_id', $termId)
          ->where('siswa_id', $siswaId)
          ->whereDate('date', $date)
          ->first();

        if ($existing) {
          // Jika sudah ada hadir/terlambat dan override tidak diizinkan, skip
          if (in_array($existing->status, ['hadir', 'terlambat'], true) && !$allowOverrideGuru) {
            continue;
          }

          $existing->update([
            'term_id'      => $termId,
            'classroom_id' => (int) $classroom->id,
            'status'       => $status,
            'time'         => $existing->time ?: $nowTime,
            'source'       => 'wali_kelas',
            'user_agent'   => $existing->user_agent ?: $ua,
          ]);
        } else {
          Attendance::create([
            'term_id'      => $termId,
            'siswa_id'     => $siswaId,
            'classroom_id' => (int) $classroom->id,
            'date'         => $date,
            'time'         => $nowTime,
            'status'       => $status,
            'source'       => 'wali_kelas',
            'user_agent'   => $ua,
          ]);
        }
      }
    });

    return redirect()
      ->route('absence.index', ['date' => $date])
      ->with('success', 'Data ketidakhadiran berhasil disimpan.');
  }

  /** DELETE /wali-kelas/absence/{attendance} */
  public function destroy(Request $request, Attendance $attendance) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $classroomId = (int) $homeroom->classroom_id;
    $termId      = (int) $homeroom->term_id;

    // Hanya data ketidakhadiran yang boleh dihapus
    abort_if(
      !in_array($attendance->status, ['sakit', 'izin', 'alpa'], true),
      403,
      'Hanya data ketidakhadiran yang dapat dihapus.'
    );

    // Kunci juga ke term aktif
    abort_if(
      (int) $attendance->term_id !== $termId,
      403,
      'Data ini bukan dari term aktif.'
    );

    // Wajib: attendance harus milik kelas binaan wali
    abort_if(
      (int) $attendance->classroom_id !== $classroomId,
      403,
      'Tidak berwenang menghapus data ini.'
    );

    // Validasi term-aware: siswa memang ada di kelas itu pada term itu (pivot)
    // Pakai '*' agar aman jika status pivot berubah (mis. moved/inactive) tapi histori tetap bisa dikelola
    $ok = HomeroomContext::siswaInClassTerm(
      $termId,
      $classroomId,
      (int) $attendance->siswa_id,
      '*'
    );

    abort_unless($ok, 403, 'Tidak berwenang menghapus data ini.');

    $attendance->delete();

    $date = $this->resolveDate($request);

    return redirect()
      ->route('absence.index', ['date' => $date])
      ->with('success', 'Data ketidakhadiran dihapus.');
  }

  /** Resolve & validasi tanggal dari query/input; default: today */
  private function resolveDate(Request $request): string {
    $raw = $request->query('date') ?? $request->input('date') ?? now()->toDateString();

    $v = Validator::make(['date' => $raw], [
      'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
    ], [
      'before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
    ]);

    return $v->fails() ? now()->toDateString() : $raw;
  }
}