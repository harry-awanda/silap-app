<?php

namespace App\Queries\AuditAttendance;

use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\Classroom;

class PerClassRecapQuery {
  public function get(int $termId, string $date, ?int $kelasId) {
    $studentsPerClass = DB::table('term_classroom_siswa as tcs')
      ->select('tcs.classroom_id', DB::raw('COUNT(DISTINCT tcs.siswa_id) as students_count'))
      ->where('tcs.term_id', $termId)
      ->where('tcs.status', 'active')
      ->when($kelasId, fn($q) => $q->where('tcs.classroom_id', $kelasId))
      ->groupBy('tcs.classroom_id')
      ->pluck('students_count', 'classroom_id');

    $countsPerClass = Attendance::query()
      ->select(
        'classroom_id',
        DB::raw("COUNT(DISTINCT CASE WHEN status='hadir' THEN siswa_id END) as hadir_count"),
        DB::raw("COUNT(DISTINCT CASE WHEN status='terlambat' THEN siswa_id END) as terlambat_count"),
        DB::raw("COUNT(DISTINCT CASE WHEN status='izin' THEN siswa_id END) as izin_count"),
        DB::raw("COUNT(DISTINCT CASE WHEN status='sakit' THEN siswa_id END) as sakit_count"),
        DB::raw("COUNT(DISTINCT CASE WHEN status='alpa' THEN siswa_id END) as alpa_count"),
        DB::raw("COUNT(DISTINCT siswa_id) as total_today_count")
      )
      ->where('term_id', $termId)
      ->whereDate('date', $date)
      ->when($kelasId, fn($q) => $q->where('classroom_id', $kelasId))
      ->groupBy('classroom_id')
      ->get()
      ->keyBy('classroom_id');

    $kelasList = Classroom::withoutActiveTerm()
      ->where('term_id', $termId)
      ->when($kelasId, fn($q) => $q->where('id', $kelasId))
      ->orderByDesc('tingkat')
      ->orderBy('nama_kelas')
      ->get(['id','nama_kelas','tingkat']);

    return $kelasList->map(function ($c) use ($studentsPerClass, $countsPerClass) {
      $cnt = $countsPerClass->get($c->id);
      return (object) [
        'id' => $c->id,
        'nama_kelas' => $c->nama_kelas,
        'tingkat' => $c->tingkat,
        'students_count' => (int) ($studentsPerClass[$c->id] ?? 0),
        'hadir_count' => (int) ($cnt->hadir_count ?? 0),
        'terlambat_count' => (int) ($cnt->terlambat_count ?? 0),
        'izin_count' => (int) ($cnt->izin_count ?? 0),
        'sakit_count' => (int) ($cnt->sakit_count ?? 0),
        'alpa_count' => (int) ($cnt->alpa_count ?? 0),
        'total_today_count' => (int) ($cnt->total_today_count ?? 0),
      ];
    })->sortByDesc('terlambat_count')->values();
  }
}