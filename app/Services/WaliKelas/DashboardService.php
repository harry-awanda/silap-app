<?php

namespace App\Services\WaliKelas;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Siswa;
use App\Models\Attendance;
use App\Support\HomeroomContext;

class DashboardService {
  /**
   * Ambil data dashboard wali kelas (rekap status, aktivitas terbaru, top terlambat).
   *
   * @return array{0:\Illuminate\Support\Collection,1:\Illuminate\Support\Collection,2:\Illuminate\Support\Collection}
   */
  public function build(int $termId, int $classroomId, string $dateYmd, int $userId, int $cacheSeconds = 90): array {
    $cacheKey = "wali:dash:rekap:v4:user={$userId}:term={$termId}:class={$classroomId}:date={$dateYmd}";

    return Cache::remember($cacheKey, now()->addSeconds($cacheSeconds), function () use ($termId, $classroomId, $dateYmd) {
      // 1) enrollment siswa per term (kelas per term)
      $siswaIds = HomeroomContext::siswaIdsInClassTerm($termId, $classroomId, 'active')
        ->map(fn($v) => (int) $v)
        ->unique()
        ->values();

      $totalSiswaKelas = $siswaIds->count();

      // base query presensi tanggal tertentu
      $baseDate = Attendance::query()
        ->where('term_id', $termId)
        ->where('classroom_id', $classroomId)
        ->whereDate('date', $dateYmd)
        ->when($totalSiswaKelas > 0, fn($q) => $q->whereIn('siswa_id', $siswaIds));

      // 2) rekap per status
      $byStatus = (clone $baseDate)
        ->select('status', DB::raw('COUNT(DISTINCT siswa_id) as jumlah'))
        ->groupBy('status')
        ->pluck('jumlah', 'status');

      $sudahTercatat = (clone $baseDate)
        ->distinct()
        ->count('siswa_id');

      $belum = max(0, $totalSiswaKelas - $sudahTercatat);

      $rekap = collect([
        'hadir'     => (int) ($byStatus['hadir'] ?? 0),
        'terlambat' => (int) ($byStatus['terlambat'] ?? 0),
        'izin'      => (int) ($byStatus['izin'] ?? 0),
        'sakit'     => (int) ($byStatus['sakit'] ?? 0),
        'alpa'      => (int) ($byStatus['alpa'] ?? 0),
        'belum'     => (int) $belum,
      ]);

      // 3) aktivitas terbaru
      $recent = (clone $baseDate)
        ->with(['siswa:id,nama_lengkap'])
        ->orderByDesc('time')
        ->limit(15)
        ->get(['id', 'siswa_id', 'status', 'time', 'date', 'classroom_id', 'term_id']);

      // 4) top terlambat bulan ini (berdasarkan dateYmd yang sedang dilihat)
      $startOfMonth = Carbon::parse($dateYmd)->startOfMonth()->toDateString();

      $top = Attendance::query()
        ->where('term_id', $termId)
        ->where('classroom_id', $classroomId)
        ->whereBetween('date', [$startOfMonth, $dateYmd])
        ->where('status', 'terlambat')
        ->when($totalSiswaKelas > 0, fn($q) => $q->whereIn('siswa_id', $siswaIds))
        ->select(
          'siswa_id',
          DB::raw('COUNT(*) as terlambat_total'),
          DB::raw('MAX(date) as last_date'),
          DB::raw('MAX(time) as last_time')
        )
        ->groupBy('siswa_id')
        ->orderByDesc('terlambat_total')
        ->limit(5)
        ->get();

      $siswaMap = Siswa::query()
        ->whereIn('id', $top->pluck('siswa_id'))
        ->get(['id', 'nama_lengkap'])
        ->keyBy('id');

      $top->transform(function ($row) use ($siswaMap) {
        $row->siswa = $siswaMap->get($row->siswa_id);
        return $row;
      });

      return [$rekap, $recent, $top];
    });
  }
}