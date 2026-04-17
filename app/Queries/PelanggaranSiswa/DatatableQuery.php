<?php

namespace App\Queries\PelanggaranSiswa;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatatableQuery {
  public static function build(Request $request, int $termId, ?int $classroomIdFilter, ?string $statusFilter) {
    return DB::table('pelanggaran_siswa as ps')
      ->join('term_classroom_siswa as tcs', function ($join) use ($termId) {
        $join->on('tcs.siswa_id', '=', 'ps.siswa_id')
          ->where('tcs.term_id', '=', $termId)
          ->where('tcs.status', '=', 'active');
      })
      ->join('siswa as s', 's.id', '=', 'ps.siswa_id')
      ->join('classrooms as c', function ($join) use ($termId) {
        $join->on('c.id', '=', 'tcs.classroom_id')
          ->where('c.term_id', '=', $termId);
      })
      ->where('ps.term_id', $termId)
      ->when($statusFilter, fn($q) => $q->where('ps.status', $statusFilter))
      ->when($classroomIdFilter !== null, fn($q) => $q->where('tcs.classroom_id', $classroomIdFilter))
      ->select([
        'ps.id',
        'ps.siswa_id',
        'ps.term_id',
        'ps.tanggal_pelanggaran',
        'ps.status',
        'ps.tindakan',
        'ps.keterangan',
        'ps.updated_at',
        'ps.created_at',
        's.nama_lengkap as siswa_nama',
        's.nis as siswa_nis',
        'c.nama_kelas as kelas_nama',
        'tcs.classroom_id as classroom_id',
      ]);
  }

  public static function applySearch($query, string $search, int $termId): void {
    $query->where(function ($w) use ($search, $termId) {
      $w->where('s.nama_lengkap', 'like', "%{$search}%")
        ->orWhere('s.nis', 'like', "%{$search}%")
        ->orWhere('c.nama_kelas', 'like', "%{$search}%")
        ->orWhere('ps.keterangan', 'like', "%{$search}%")
        ->orWhere('ps.status', 'like', "%{$search}%")
        ->orWhere('ps.tindakan', 'like', "%{$search}%")
        ->orWhereExists(function ($sub) use ($search, $termId) {
          $sub->select(DB::raw(1))
            ->from('pelanggaran_siswa_pelanggaran as pp')
            ->join('pelanggaran as p', 'p.id', '=', 'pp.pelanggaran_id')
            ->whereColumn('pp.pelanggaran_siswa_id', 'ps.id')
            ->where('pp.term_id', $termId)
            ->where('p.nama', 'like', "%{$search}%");
        });
    });
  }
}