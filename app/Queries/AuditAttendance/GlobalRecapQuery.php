<?php

namespace App\Queries\AuditAttendance;

use Illuminate\Support\Facades\DB;
use App\Models\Attendance;

class GlobalRecapQuery {
  public function get(int $termId, string $date, ?int $kelasId): array {
    $rows = Attendance::query()
      ->where('term_id', $termId)
      ->whereDate('date', $date)
      ->when($kelasId, fn($q) => $q->where('classroom_id', $kelasId))
      ->get(['status']);

    $rekap = $rows->countBy('status')->toArray();

    $totalSiswa = (int) DB::table('term_classroom_siswa as tcs')
      ->where('tcs.term_id', $termId)
      ->where('tcs.status', 'active')
      ->when($kelasId, fn($q) => $q->where('tcs.classroom_id', $kelasId))
      ->distinct()
      ->count('tcs.siswa_id');

    $totalTercatat = (int) Attendance::query()
      ->where('term_id', $termId)
      ->whereDate('date', $date)
      ->when($kelasId, fn($q) => $q->where('classroom_id', $kelasId))
      ->whereExists(function ($q) use ($termId, $kelasId) {
        $q->select(DB::raw(1))
          ->from('term_classroom_siswa as tcs')
          ->whereColumn('tcs.siswa_id', 'attendances.siswa_id')
          ->whereColumn('tcs.classroom_id', 'attendances.classroom_id')
          ->where('tcs.term_id', $termId)
          ->where('tcs.status', 'active')
          ->when($kelasId, fn($w) => $w->where('tcs.classroom_id', $kelasId));
      })
      ->distinct()
      ->count('siswa_id');

    $rekap['belum'] = max(0, $totalSiswa - $totalTercatat);

    return [
      'rekapStatus' => $rekap,
      'totalSiswa' => $totalSiswa,
      'totalTercatat' => $totalTercatat,
    ];
  }
}