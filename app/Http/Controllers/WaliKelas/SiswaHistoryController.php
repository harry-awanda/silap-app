<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\{Attendance, HomeroomAssignment, PelanggaranSiswa, Siswa};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiswaHistoryController extends Controller {
  public function index(Request $request) {
    $title = 'Riwayat Siswa Binaan';
    $guruId = $this->guruId($request);

    $assignments = $this->assignmentsForGuru($guruId);
    $selectedAssignmentId = (int) $request->query('assignment_id', (int) ($assignments->first()?->id ?? 0));
    $assignment = $selectedAssignmentId
      ? $this->findAssignmentForGuru($guruId, $selectedAssignmentId)
      : null;

    $siswa = $assignment ? $this->studentsForAssignment($assignment) : collect();

    return view('wali-kelas.siswa.history.index', compact(
      'title',
      'assignments',
      'assignment',
      'siswa'
    ));
  }

  public function show(Request $request, int $assignment, Siswa $siswa) {
    $guruId = $this->guruId($request);
    $assignment = $this->findAssignmentForGuru($guruId, $assignment);

    abort_unless(
      DB::table('term_classroom_siswa')
        ->where('term_id', $assignment->term_id)
        ->where('classroom_id', $assignment->classroom_id)
        ->where('siswa_id', $siswa->id)
        ->exists(),
      403,
      'Siswa tidak tercatat pada riwayat kelas ini.'
    );

    $attendances = Attendance::withoutActiveTerm()
      ->where('term_id', $assignment->term_id)
      ->where('classroom_id', $assignment->classroom_id)
      ->where('siswa_id', $siswa->id)
      ->orderByDesc('date')
      ->limit(30)
      ->get(['date', 'time', 'status', 'source', 'notes']);

    $violations = PelanggaranSiswa::withoutActiveTerm()
      ->where('term_id', $assignment->term_id)
      ->where('siswa_id', $siswa->id)
      ->orderByDesc('tanggal_pelanggaran')
      ->limit(20)
      ->get(['tanggal_pelanggaran', 'keterangan', 'status', 'tindakan']);

    $title = 'Detail Riwayat Siswa';

    return view('wali-kelas.siswa.history.show', compact(
      'title',
      'assignment',
      'siswa',
      'attendances',
      'violations'
    ));
  }

  private function guruId(Request $request): int {
    $guruId = (int) optional($request->user()?->guru)->id;
    abort_if(!$guruId, 403, 'Akun Anda belum terhubung dengan data guru.');

    return $guruId;
  }

  private function assignmentsForGuru(int $guruId) {
    return HomeroomAssignment::withoutActiveTerm()
      ->with([
        'term',
        'classroom' => fn ($query) => $query->withoutActiveTerm(),
      ])
      ->where('guru_id', $guruId)
      ->orderByDesc('term_id')
      ->orderByDesc('started_at')
      ->get();
  }

  private function findAssignmentForGuru(int $guruId, int $assignmentId): HomeroomAssignment {
    $assignment = HomeroomAssignment::withoutActiveTerm()
      ->with([
        'term',
        'classroom' => fn ($query) => $query->withoutActiveTerm(),
      ])
      ->where('guru_id', $guruId)
      ->find($assignmentId);

    abort_if(!$assignment, 404, 'Riwayat wali kelas tidak ditemukan.');

    return $assignment;
  }

  private function studentsForAssignment(HomeroomAssignment $assignment) {
    return Siswa::query()
      ->join('term_classroom_siswa as tcs', 'tcs.siswa_id', '=', 'siswa.id')
      ->where('tcs.term_id', $assignment->term_id)
      ->where('tcs.classroom_id', $assignment->classroom_id)
      ->orderBy('siswa.nama_lengkap')
      ->get([
        'siswa.id',
        'siswa.nis',
        'siswa.nama_lengkap',
        'siswa.jenis_kelamin',
        'siswa.photo',
        'tcs.status as placement_status',
      ]);
  }
}
