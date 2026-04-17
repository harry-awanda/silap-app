<?php

namespace App\Services\WaliKelas;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Excel as ExcelFormat;

use App\Models\Attendance;
use App\Support\HomeroomContext;

class WaliAuditAttendanceExportService {
  public function download(int $termId, int $classroomId, string $classroomName, string $date, ?string $status) {
    $status = $status ?: null;

    $siswaIdsTerm = HomeroomContext::siswaIdsInClassTerm($termId, $classroomId, 'active')
      ->map(fn($v) => (int) $v)
      ->all();

    $rows = Attendance::query()
      ->with(['siswa:id,nama_lengkap,nis'])
      ->where('term_id', $termId)
      ->where('classroom_id', $classroomId)
      ->whereDate('date', $date)
      ->when($status, fn($qq) => $qq->where('status', $status))
      ->when(!empty($siswaIdsTerm), fn($qq) => $qq->whereIn('siswa_id', $siswaIdsTerm))
      ->orderBy('time')
      ->get([
        'id', 'siswa_id', 'classroom_id', 'date', 'time', 'status',
        'latitude', 'longitude', 'accuracy_m', 'source', 'user_agent',
      ]);

    $data = collect([
      [
        'Tanggal', 'Waktu', 'NIS', 'Nama', 'Kelas', 'Status',
        'Latitude', 'Longitude', 'Accuracy(m)', 'Source', 'User-Agent'
      ]
    ]);

    foreach ($rows as $r) {
      $data->push([
        $r->date instanceof \Carbon\Carbon ? $r->date->format('Y-m-d') : $r->date,
        $r->time ? Carbon::parse($r->time)->format('H:i:s') : '',
        $r->siswa->nis ?? '',
        $r->siswa->nama_lengkap ?? '',
        $classroomName,
        $r->status,
        $r->latitude,
        $r->longitude,
        $r->accuracy_m,
        $r->source,
        $r->user_agent,
      ]);
    }

    $safeClass = preg_replace('/[^A-Za-z0-9_\- ]+/', '', $classroomName ?: 'Kelas');
    $filename  = 'Audit_' . $safeClass . '_' . $date . '.xlsx';

    return Excel::download(
      new class($data) implements FromCollection {
        public function __construct(private Collection $data) {}
        public function collection() { return $this->data; }
      },
      $filename,
      ExcelFormat::XLSX
    );
  }
}