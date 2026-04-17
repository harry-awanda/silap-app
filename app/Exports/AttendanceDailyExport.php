<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{
    FromCollection, WithHeadings, WithMapping, ShouldAutoSize
};

class AttendanceDailyExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize {
  public function __construct(
    public int $termId,
    public string $date,
    public ?string $status = null,
    public ?int $classroomId = null,
  ) {}

  /** @return \Illuminate\Support\Collection */
  public function collection(): Collection {
    // ====== STATUS: BELUM (TERM-SAFE via pivot) ======
    if ($this->status === 'belum') {
      $rows = DB::table('term_classroom_siswa as tcs')
        ->join('siswa as s', 's.id', '=', 'tcs.siswa_id')
        ->join('classrooms as c', 'c.id', '=', 'tcs.classroom_id')
        ->leftJoin('attendances as a', function ($join) {
          $join->on('a.siswa_id', '=', 'tcs.siswa_id')
            ->where('a.term_id', '=', $this->termId)
            ->whereDate('a.date', '=', $this->date);
        })
        ->where('tcs.term_id', $this->termId)
        ->where('tcs.status', 'active')
        ->where('c.term_id', $this->termId)
        ->when($this->classroomId, fn($q) => $q->where('tcs.classroom_id', $this->classroomId))
        ->whereNull('a.id')
        ->orderBy('c.nama_kelas', 'asc')
        ->orderBy('s.nama_lengkap', 'asc')
        ->get([
          DB::raw("'" . $this->date . "' as date"),
          DB::raw("NULL as time"),
          's.nis as nis',
          's.nama_lengkap as nama',
          'c.nama_kelas as kelas',
          DB::raw("'belum' as status"),
          DB::raw("NULL as latitude"),
          DB::raw("NULL as longitude"),
          DB::raw("NULL as accuracy_m"),
          DB::raw("NULL as source"),
          DB::raw("NULL as user_agent"),
        ]);

      return collect($rows);
    }

    // ====== STATUS LAIN (TERM-SAFE) ======
    $rows = DB::table('attendances as at')
      ->join('siswa as s', 's.id', '=', 'at.siswa_id')
      // kelas diambil dari attendance.classroom_id (lebih benar untuk term)
      ->leftJoin('classrooms as c', 'c.id', '=', 'at.classroom_id')
      ->where('at.term_id', $this->termId)
      ->whereDate('at.date', $this->date)
      ->when($this->status, fn($q) => $q->where('at.status', $this->status))
      ->when($this->classroomId, fn($q) => $q->where('at.classroom_id', $this->classroomId))
      ->orderBy('c.nama_kelas', 'asc')
      ->orderBy('at.time', 'asc')
      ->orderBy('s.nama_lengkap', 'asc')
      ->get([
        'at.date',
        'at.time',
        's.nis as nis',
        's.nama_lengkap as nama',
        'c.nama_kelas as kelas',
        'at.status',
        'at.latitude',
        'at.longitude',
        'at.accuracy_m',
        'at.source',
        'at.user_agent',
      ]);

    return collect($rows);
  }

  public function headings(): array {
    return [
      'Tanggal',
      'Waktu',
      'NIS',
      'Nama',
      'Kelas',
      'Status',
      'Latitude',
      'Longitude',
      'Accuracy (m)',
      'Source',
      'User-Agent',
    ];
  }

  public function map($row): array {
    $toDash = fn($v) => ($v === null || $v === '') ? '-' : $v;

    return [
      $row->date ?? $this->date,
      $toDash($row->time ?? null),
      $toDash($row->nis ?? null),
      $toDash($row->nama ?? null),
      $toDash($row->kelas ?? null),
      isset($row->status) ? ucfirst($row->status) : 'Belum',
      $row->latitude ?? '',
      $row->longitude ?? '',
      $row->accuracy_m ?? '',
      $toDash($row->source ?? null),
      $toDash($row->user_agent ?? null),
    ];
  }
}