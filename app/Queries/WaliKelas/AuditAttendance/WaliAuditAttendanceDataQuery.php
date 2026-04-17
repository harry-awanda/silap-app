<?php

namespace App\Queries\WaliKelas\AuditAttendance;

use Carbon\Carbon;
use App\Models\{Siswa, Attendance};
use App\Support\HomeroomContext;

class WaliAuditAttendanceDataQuery {
  public function get(int $termId, int $classroomId, string $date, ?string $status, string $q): array {
    $status = strtolower((string) ($status ?? ''));

    // ✅ Siswa binaan term aktif (single source of truth via HomeroomContext)
    $siswaIdsTerm = HomeroomContext::siswaIdsInClassTerm($termId, $classroomId, 'active')
      ->map(fn($v) => (int) $v)
      ->all();

    // kalau kosong, langsung return kosong (lebih aman dan hemat query)
    if (empty($siswaIdsTerm)) return [];

    // === KHUSUS: status = "belum" ===
    if ($status === 'belum') {
      $presentIds = Attendance::query()
        ->where('term_id', $termId)
        ->where('classroom_id', $classroomId)
        ->whereDate('date', $date)
        ->distinct()
        ->pluck('siswa_id')
        ->map(fn($v) => (int) $v)
        ->all();

      $siswas = Siswa::query()
        ->whereIn('id', $siswaIdsTerm)
        ->when($q !== '', fn($qq) => $qq->where(function ($w) use ($q) {
          $w->where('nama_lengkap', 'like', "%{$q}%")
            ->orWhere('nis', 'like', "%{$q}%");
        }))
        ->when(!empty($presentIds), fn($qq) => $qq->whereNotIn('id', $presentIds))
        ->orderBy('nama_lengkap')
        ->get(['id', 'nis', 'nama_lengkap']);

      return $siswas->map(function ($s) use ($classroomId) {
        return [
          'attendance_id' => null,
          'siswa_id'      => $s->id,
          'classroom_id'  => $classroomId,
          'time'          => null,
          'nis'           => $s->nis,
          'nama'          => $s->nama_lengkap,
          'status'        => 'belum',
          'status_badge'  => '<span class="badge bg-secondary">Belum Presensi</span>',
          'detail_json'   => json_encode([
            'date'       => null,
            'time'       => null,
            'latitude'   => null,
            'longitude'  => null,
            'accuracy_m' => null,
            'source'     => null,
            'user_agent' => null,
          ], JSON_UNESCAPED_SLASHES),
        ];
      })->values()->all();
    }

    // === Data normal (sudah punya attendance) ===
    $rows = Attendance::query()
      ->with(['siswa:id,nama_lengkap,nis'])
      ->where('term_id', $termId)
      ->where('classroom_id', $classroomId)
      ->whereDate('date', $date)
      ->when($status !== '' && $status !== null, fn($qq) => $qq->where('status', $status))
      ->when($q !== '', function ($qq) use ($q) {
        $qq->whereHas('siswa', function ($qs) use ($q) {
          $qs->where('nama_lengkap', 'like', "%{$q}%")
            ->orWhere('nis', 'like', "%{$q}%");
        });
      })
      ->whereIn('siswa_id', $siswaIdsTerm) // extra safety
      ->orderBy('time')
      ->get([
        'id', 'siswa_id', 'classroom_id', 'date', 'time', 'status',
        'latitude', 'longitude', 'accuracy_m', 'source', 'user_agent',
      ]);

    $badgeMap = [
      'hadir'     => 'success',
      'terlambat' => 'warning',
      'izin'      => 'info',
      'sakit'     => 'primary',
      'alpa'      => 'danger',
    ];

    return $rows->map(function ($r) use ($badgeMap) {
      $stLower = strtolower((string) $r->status);
      $cls     = $badgeMap[$stLower] ?? 'secondary';

      return [
        'attendance_id' => $r->id,
        'siswa_id'      => $r->siswa_id,
        'classroom_id'  => $r->classroom_id,
        'time'          => $r->time ? Carbon::parse($r->time)->format('H:i') : null,
        'nis'           => $r->siswa->nis ?? '-',
        'nama'          => $r->siswa->nama_lengkap ?? '-',
        'status'        => $r->status,
        'status_badge'  => '<span class="badge bg-label-' . $cls . '">' . ucfirst($stLower) . '</span>',
        'detail_json'   => json_encode([
          'date'       => $r->date instanceof \Carbon\Carbon ? $r->date->format('Y-m-d') : $r->date,
          'time'       => $r->time ? Carbon::parse($r->time)->format('H:i:s') : null,
          'source'     => $r->source,
          'latitude'   => $r->latitude,
          'longitude'  => $r->longitude,
          'accuracy_m' => $r->accuracy_m,
          'user_agent' => $r->user_agent,
        ], JSON_UNESCAPED_SLASHES),
      ];
    })->values()->all();
  }
}