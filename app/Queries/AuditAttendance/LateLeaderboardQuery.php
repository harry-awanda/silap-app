<?php

namespace App\Queries\AuditAttendance;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Attendance;

class LateLeaderboardQuery {
  public function build(int $termId, string $start, string $end, ?int $kelasId, Request $request) {

    $query = Attendance::query()
      ->from('attendances')
      ->join('siswa', 'siswa.id', '=', 'attendances.siswa_id')
      ->leftJoin('classrooms', 'classrooms.id', '=', 'attendances.classroom_id')
      ->where('attendances.term_id', $termId)
      ->where('attendances.status', 'terlambat')
      ->whereBetween('attendances.date', [$start, $end])
      ->when($kelasId, fn($q) => $q->where('attendances.classroom_id', $kelasId))
      ->groupBy('attendances.siswa_id', 'siswa.nama_lengkap', 'classrooms.nama_kelas')
      ->selectRaw("
        attendances.siswa_id,
        siswa.nama_lengkap as nama,
        classrooms.nama_kelas as kelas,
        COUNT(*) as terlambat_total,
        MAX(attendances.date) as last_date,
        MAX(attendances.time) as last_time
      ");

    // ✅ RETURN DataTable instance (bukan JsonResponse)
    return DataTables::of($query)

      ->addColumn('last_at', function ($row) {
        $d = $row->last_date ? Carbon::parse($row->last_date)->format('d-m') : '-';
        $t = $row->last_time ? Carbon::parse($row->last_time)->format('H:i') : '';
        return trim($d . ' ' . $t) ?: '-';
      })

      ->filterColumn('nama', fn($q, $kw) =>
        $q->whereRaw('LOWER(siswa.nama_lengkap) LIKE ?', ['%' . strtolower($kw) . '%'])
      )
      ->filterColumn('kelas', fn($q, $kw) =>
        $q->whereRaw('LOWER(classrooms.nama_kelas) LIKE ?', ['%' . strtolower($kw) . '%'])
      )

      // ✅ mapping sorting
      ->orderColumn('nama', fn($q, $order) => $q->orderBy('siswa.nama_lengkap', $order))
      ->orderColumn('kelas', fn($q, $order) => $q->orderBy('classrooms.nama_kelas', $order))
      ->orderColumn('terlambat_total', fn($q, $order) => $q->orderByRaw("terlambat_total {$order}"))
      ->orderColumn('last_at', fn($q, $order) => $q->orderBy('last_date', $order)->orderBy('last_time', $order));
  }
}
