<?php

namespace App\Queries\AuditAttendance;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Attendance;

class DetailDatatableQuery {
  public function build(int $termId, string $date, ?string $status, ?int $kelasId, Request $request) {
    if ($status === 'belum') {
      $query = DB::table('term_classroom_siswa as tcs')
        ->join('siswa as s', 's.id', '=', 'tcs.siswa_id')
        ->join('classrooms as c', 'c.id', '=', 'tcs.classroom_id')
        ->leftJoin('attendances as a', function ($join) use ($termId, $date) {
          $join->on('a.siswa_id', '=', 'tcs.siswa_id')
            ->on('a.classroom_id', '=', 'tcs.classroom_id')
            ->where('a.term_id', '=', $termId)
            ->whereDate('a.date', '=', $date);
        })
        ->where('tcs.term_id', $termId)
        ->where('tcs.status', 'active')
        ->where('c.term_id', $termId)
        ->when($kelasId, fn($q) => $q->where('tcs.classroom_id', $kelasId))
        ->whereNull('a.id')
        ->select([
          DB::raw('s.id as sid'),
          DB::raw('s.nama_lengkap as nama'),
          DB::raw('c.nama_kelas as kelas'),
          DB::raw('NULL as time'),
          DB::raw("'belum' as status"),
          DB::raw('NULL as latitude'),
          DB::raw('NULL as longitude'),
          DB::raw('NULL as accuracy_m'),
          DB::raw('NULL as source'),
          DB::raw('NULL as user_agent'),
        ]);

      return DataTables::of($query)
        ->addColumn('waktu', fn() => '-')
        ->addColumn('status_badge', fn() => '<span class="badge bg-label-secondary">Belum</span>')
        ->order(function ($q) use ($request) {
          $orders = $request->get('order', []);
          if (empty($orders)) $q->orderBy('nama', 'asc');
        })
        ->filterColumn('nama', fn($q, $kw) => $q->whereRaw('LOWER(nama) LIKE ?', ["%" . strtolower($kw) . "%"]))
        ->filterColumn('kelas', fn($q, $kw) => $q->whereRaw('LOWER(kelas) LIKE ?', ["%" . strtolower($kw) . "%"]))
        ->rawColumns(['status_badge']);
    }

    $query = Attendance::query()
      ->join('siswa as s', 's.id', '=', 'attendances.siswa_id')
      ->leftJoin('classrooms as c', 'c.id', '=', 'attendances.classroom_id')
      ->where('attendances.term_id', $termId)
      ->whereExists(function ($q) use ($termId) {
        $q->select(DB::raw(1))
          ->from('term_classroom_siswa as tcs')
          ->whereColumn('tcs.siswa_id', 'attendances.siswa_id')
          ->whereColumn('tcs.classroom_id', 'attendances.classroom_id')
          ->where('tcs.term_id', $termId)
          ->where('tcs.status', 'active');
      })
      ->whereDate('attendances.date', $date)
      ->when($status, fn($q) => $q->where('attendances.status', $status))
      ->when($kelasId, fn($q) => $q->where('attendances.classroom_id', $kelasId))
      ->select([
        'attendances.id',
        'attendances.date',
        'attendances.time',
        'attendances.status',
        'attendances.latitude',
        'attendances.longitude',
        'attendances.accuracy_m',
        'attendances.source',
        'attendances.user_agent',
        'attendances.classroom_id',
        'attendances.term_id',
        's.nama_lengkap as nama',
        's.nis as nis',
        'c.nama_kelas as kelas',
      ]);

    return DataTables::of($query)
      ->editColumn('time', fn($row) => $row->time ? Carbon::parse($row->time)->format('H:i:s') : '-')
      ->addColumn('waktu', fn($row) => $row->time ? Carbon::parse($row->time)->format('H:i:s') : '-')
      ->addColumn('status_badge', function ($row) {
        $labelMap = [
          'hadir' => 'success',
          'terlambat' => 'warning',
          'izin' => 'info',
          'sakit' => 'primary',
          'alpa' => 'danger',
        ];
        $lbl = $labelMap[$row->status] ?? 'secondary';
        return '<span class="badge bg-label-' . $lbl . '">' . ucfirst($row->status) . '</span>';
      })
      ->rawColumns(['status_badge']);
  }
}