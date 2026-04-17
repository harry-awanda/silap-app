<?php

namespace App\Services\Face;

use App\Models\{Attendance, AttendanceFaceLog, FaceProfile, Siswa};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FaceAttendanceService {

  public function __construct(
    private FaceVerifyService $verify,
  ) {}

  public function logAttempt(array $data): void {
    AttendanceFaceLog::create($data);
  }

  public function submit(
    Siswa $siswa,
    int $termId,
    int $classroomId,
    array $geo,
    array $device,
    bool $livenessPassed,
    ?float $livenessScore,
    string $liveRawBinary,
    string $modelVersion = 'v1',
  ): Attendance {

    $now    = Carbon::now();
    $today  = $now->toDateString();

    return DB::transaction(function () use (
      $siswa, $termId, $classroomId, $geo, $device,
      $livenessPassed, $livenessScore, $liveRawBinary, $modelVersion,
      $now, $today
    ) {

      // 1) cek sudah ada presensi hari ini
      $existing = Attendance::query()
        ->where('term_id', $termId)
        ->where('siswa_id', $siswa->id)
        ->whereDate('date', $today)
        ->first();

      if ($existing) {
        // kalau existing beda kelas -> konflik
        if ((int) $existing->classroom_id !== $classroomId) {
          abort(409, 'Presensi hari ini sudah tercatat pada kelas lain. Hubungi admin untuk koreksi data.');
        }
        abort(409, 'Presensi hari ini sudah ada.');
      }

      // 2) liveness wajib
      if (!$livenessPassed) {
        $this->logAttempt([
          'term_id' => $termId,
          'siswa_id' => $siswa->id,
          'classroom_id' => $classroomId,
          'date' => $today,
          'time' => $now->format('H:i:s'),
          'result' => 'fail',
          'reason' => 'Liveness gagal',
          'similarity' => null,
          'liveness_passed' => false,
          'liveness_score' => $livenessScore,
          'device_id' => $device['device_id'] ?? null,
          'user_agent' => $device['user_agent'] ?? null,
          'latitude' => $geo['latitude'] ?? null,
          'longitude'=> $geo['longitude'] ?? null,
          'accuracy_m'=> $geo['accuracy_m'] ?? null,
        ]);
        abort(422, 'Liveness gagal. Ulangi.');
      }

      // 3) harus punya face profile aktif
      $profile = FaceProfile::query()
        ->where('siswa_id', $siswa->id)
        ->where('is_active', true)
        ->latest('id')
        ->first();

      if (!$profile) {
        $this->logAttempt([
          'term_id' => $termId,
          'siswa_id' => $siswa->id,
          'classroom_id' => $classroomId,
          'date' => $today,
          'time' => $now->format('H:i:s'),
          'result' => 'fail',
          'reason' => 'Belum enroll wajah',
          'liveness_passed' => true,
          'liveness_score' => $livenessScore,
          'device_id' => $device['device_id'] ?? null,
          'user_agent' => $device['user_agent'] ?? null,
          'latitude' => $geo['latitude'] ?? null,
          'longitude'=> $geo['longitude'] ?? null,
          'accuracy_m'=> $geo['accuracy_m'] ?? null,
        ]);
        abort(403, 'Anda belum mendaftarkan wajah. Silakan lakukan enrollment di sekolah.');
      }

      // 4) verifikasi similarity (engine)
      $sim = $this->verify->bestSimilarity($profile, $liveRawBinary, $modelVersion);

      // engine belum siap -> blok aman
      if ($sim === null) {
        $this->logAttempt([
          'term_id' => $termId,
          'siswa_id' => $siswa->id,
          'classroom_id' => $classroomId,
          'date' => $today,
          'time' => $now->format('H:i:s'),
          'result' => 'fail',
          'reason' => 'Data embedding tidak siap / tidak valid',
          'similarity' => null,
          'liveness_passed' => true,
          'liveness_score' => $livenessScore,
          'device_id' => $device['device_id'] ?? null,
          'user_agent' => $device['user_agent'] ?? null,
          'latitude' => $geo['latitude'] ?? null,
          'longitude'=> $geo['longitude'] ?? null,
          'accuracy_m'=> $geo['accuracy_m'] ?? null,
        ]);
        abort(422, 'Data wajah belum siap atau tidak valid. Silakan ulangi enrollment atau hubungi admin.');
      }

      $threshold = (float) config('presensi.face.similarity_threshold', 0.82);
      if ($sim < $threshold) {
        $this->logAttempt([
          'term_id' => $termId,
          'siswa_id' => $siswa->id,
          'classroom_id' => $classroomId,
          'date' => $today,
          'time' => $now->format('H:i:s'),
          'result' => 'fail',
          'reason' => 'Wajah tidak cocok',
          'similarity' => $sim,
          'liveness_passed' => true,
          'liveness_score' => $livenessScore,
          'device_id' => $device['device_id'] ?? null,
          'user_agent' => $device['user_agent'] ?? null,
          'latitude' => $geo['latitude'] ?? null,
          'longitude'=> $geo['longitude'] ?? null,
          'accuracy_m'=> $geo['accuracy_m'] ?? null,
        ]);
        abort(422, 'Wajah tidak cocok. Coba ulangi.');
      }

      // 5) status hadir/terlambat
      $cutoff = Carbon::createFromFormat('H:i', config('presensi.cutoff_time', '07:15'));
      $status = $now->gt($cutoff) ? 'terlambat' : 'hadir';

      // 6) simpan attendance (final)
      $att = Attendance::create([
        'term_id'        => $termId,
        'siswa_id'       => $siswa->id,
        'classroom_id'   => $classroomId,
        'date'           => $today,
        'time'           => $now->format('H:i:s'),
        'status'         => $status,
        'source'         => 'face',
        'similarity'     => $sim,
        'liveness_passed'=> true,
        'notes'          => null,
        'latitude'       => $geo['latitude'] ?? null,
        'longitude'      => $geo['longitude'] ?? null,
        'accuracy_m'     => $geo['accuracy_m'] ?? null,
        'user_agent'     => $device['user_agent'] ?? null,
      ]);

      // 7) log pass
      $this->logAttempt([
        'term_id' => $termId,
        'siswa_id' => $siswa->id,
        'classroom_id' => $classroomId,
        'date' => $today,
        'time' => $now->format('H:i:s'),
        'result' => 'pass',
        'reason' => 'Presensi wajah sukses',
        'similarity' => $sim,
        'liveness_passed' => true,
        'liveness_score' => $livenessScore,
        'device_id' => $device['device_id'] ?? null,
        'user_agent' => $device['user_agent'] ?? null,
        'latitude' => $geo['latitude'] ?? null,
        'longitude'=> $geo['longitude'] ?? null,
        'accuracy_m'=> $geo['accuracy_m'] ?? null,
      ]);

      return $att;
    });
  }
}