<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AutoAlpaService {
  public function __construct(private SchoolCalendarService $calendar) {}

  /** Tentukan target date: sebelum run_cutoff → hari sekolah sebelumnya. */
  public function resolveTargetDate(): Carbon {
    $tz   = config('app.timezone', 'Asia/Jakarta');
    $now  = now($tz);
    $cut  = Carbon::parse(config('presensi.auto_alpa.run_cutoff', '15:00'), $tz)
    ->setDate($now->year, $now->month, $now->day);

    $target = $now->gte($cut) ? $now->copy() : $this->calendar->previousSchoolDay($now);
    if (!$this->calendar->isSchoolDay($target)) {
      $target = $this->calendar->previousSchoolDay($target);
    }
    return $target->startOfDay();
  }

  /** Jalankan untuk tanggal tertentu (idempoten & menghormati libur). */
  public function runForDate(Carbon $date): array {
    if (!config('presensi.auto_alpa.enabled', true)) {
      return ['skipped' => true, 'reason' => 'disabled'];
    }
    if (!$this->calendar->isSchoolDay($date)) {
      return ['skipped' => true, 'reason' => 'non-school-day'];
    }

    // Kunci harian agar tidak double-run
    $key = "auto_alpa:" . $date->toDateString();
    if (!Cache::add($key, true, $date->copy()->endOfDay())) {
      return ['skipped' => true, 'reason' => 'already-run-today'];
    }

    $defaultTime = config('presensi.auto_alpa.default_time', '00:00:00');

    // Ambil semua siswa aktif (asumsikan punya classroom_id)
    $siswa = Siswa::query()->select('id', 'classroom_id')->get();
    $siswaIds        = $siswa->pluck('id');
    $classroomBySid  = $siswa->pluck('classroom_id', 'id');

    // Cari siapa yang SUDAH punya presensi di tanggal tsb.
    $existing = Attendance::query()
      ->whereDate('date', $date)  // kolom kamu 'date'
      ->pluck('siswa_id')
      ->all();

    $missingIds = $siswaIds->diff($existing);

    $inserted = 0;
    DB::beginTransaction();
    try {
      foreach ($missingIds as $sid) {
        $cid = $classroomBySid[$sid] ?? null;
        // karena classroom_id non-nullable, skip jika siswa belum punya kelas
        if (empty($cid)) continue;

        Attendance::create([
          'siswa_id'     => $sid,
          'classroom_id' => $cid,
          'status'       => 'alpa',
          'source'       => 'system',                 // asal input otomatis
          'date'         => $date->toDateString(),    // kolom tanggal kamu
          'time'         => $defaultTime,             // wajib karena non-nullable
          'latitude'     => null,
          'longitude'    => null,
          'accuracy_m'   => null,
          'user_agent'   => null,
          'auto_marked'  => true,                     // butuh kolom opsional tadi
        ]);
        $inserted++;
      }
      DB::commit();
    } catch (\Throwable $e) {
      DB::rollBack();
      Cache::forget($key);
      throw $e;
    }

    return [
      'skipped'  => false,
      'date'     => $date->toDateString(),
      'inserted' => $inserted,
      'total'    => $siswaIds->count(),
    ];
  }

  public function auto(): array {
    $target = $this->resolveTargetDate();
    return $this->runForDate($target);
  }
}