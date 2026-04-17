<?php

namespace App\Services\Face;

use App\Models\{AttendanceFaceLog, FaceEmbedding, FaceProfile, Siswa};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FaceEnrollmentService {
  public function __construct(
    private FaceProfileService $profiles,
    private FaceCryptoService $crypto,
  ) {}

  public function startSession(int $userId, array $geo, array $device): array {
    $sessionId = 'enroll:' . $userId . ':' . bin2hex(random_bytes(16));

    // challenge sederhana (nanti UI tahap 3)
    $challenge = ['type' => 'blink_twice'];

    Cache::put($sessionId, [
      'user_id'   => $userId,
      'challenge' => $challenge,
      'started'   => now()->timestamp,
      'geo'       => $geo,
      'device'    => $device,
    ], now()->addSeconds(config('presensi.face.enroll_session_ttl_seconds', 120)));

    return ['session_id' => $sessionId, 'challenge' => $challenge];
  }

  public function consumeSession(string $sessionId, int $userId): array {
    $payload = Cache::get($sessionId);
    if (!$payload || (int)$payload['user_id'] !== $userId) {
      abort(419, 'Sesi enrollment sudah kedaluwarsa. Ulangi.');
    }
    Cache::forget($sessionId);
    return $payload;
  }

  public function logAttempt(array $data): void {
    AttendanceFaceLog::create($data);
  }

  public function submitEnrollment(
    Siswa $siswa,
    int $termId,
    int $classroomId,
    array $geo,
    array $device,
    bool $livenessPassed,
    ?float $livenessScore,
    string $embeddingRawBinary,
    string $modelVersion = 'v1',
    ?array $meta = null,
    ?int $actorUserId = null,
  ): FaceProfile {
    return $this->submitEnrollmentMulti(
      siswa: $siswa,
      termId: $termId,
      classroomId: $classroomId,
      geo: $geo,
      device: $device,
      livenessPassed: $livenessPassed,
      livenessScore: $livenessScore,
      embeddingsRawBinary: [$embeddingRawBinary],
      modelVersion: $modelVersion,
      meta: $meta,
      actorUserId: $actorUserId,
    );
  }

  /**
   * ✅ Multi-sample enrollment
   * - create profile once
   * - save N embeddings (1..3)
   */
  public function submitEnrollmentMulti(
    Siswa $siswa,
    int $termId,
    int $classroomId,
    array $geo,
    array $device,
    bool $livenessPassed,
    ?float $livenessScore,
    array $embeddingsRawBinary,
    string $modelVersion = 'v1',
    ?array $meta = null,
    ?int $actorUserId = null,
  ): FaceProfile {

    return DB::transaction(function () use (
      $siswa, $termId, $classroomId, $geo, $device,
      $livenessPassed, $livenessScore, $embeddingsRawBinary,
      $modelVersion, $meta, $actorUserId
    ) {
      // 1) wajib liveness
      if (!$livenessPassed) {
        $this->logAttempt([
          'term_id' => $termId,
          'siswa_id' => $siswa->id,
          'classroom_id' => $classroomId,
          'date' => now()->toDateString(),
          'time' => now()->format('H:i:s'),
          'result' => 'fail',
          'reason' => 'Liveness gagal',
          'liveness_passed' => false,
          'liveness_score' => $livenessScore,
          'device_id' => $device['device_id'] ?? null,
          'user_agent' => $device['user_agent'] ?? null,
          'latitude' => $geo['latitude'] ?? null,
          'longitude'=> $geo['longitude'] ?? null,
          'accuracy_m'=> $geo['accuracy_m'] ?? null,
        ]);
        abort(422, 'Liveness gagal. Ulangi enrollment.');
      }

      // 2) guard minimal 1 embedding
      $embeddingsRawBinary = array_values(array_filter($embeddingsRawBinary, fn($x) => is_string($x) && $x !== ''));
      if (count($embeddingsRawBinary) < 1) {
        abort(422, 'Embedding kosong. Ulangi enrollment.');
      }
      // optional: batasi max 3 di sisi service juga
      $embeddingsRawBinary = array_slice($embeddingsRawBinary, 0, 3);

      // 3) nonaktifkan profile aktif lama (jaga 1 aktif)
      $this->profiles->deactivateActiveProfiles($siswa, $actorUserId);

      // 4) buat profile baru
      $profile = $this->profiles->createActiveProfile($siswa, $actorUserId);

      // 5) simpan semua embedding terenkripsi
      foreach ($embeddingsRawBinary as $raw) {
        $encrypted = $this->crypto->encryptEmbedding($raw);

        FaceEmbedding::create([
          'face_profile_id' => $profile->id,
          'embedding'       => $encrypted,
          'model_version'   => $modelVersion,
          'meta'            => $meta,
        ]);
      }

      // 6) log pass
      $this->logAttempt([
        'term_id' => $termId,
        'siswa_id' => $siswa->id,
        'classroom_id' => $classroomId,
        'date' => now()->toDateString(),
        'time' => now()->format('H:i:s'),
        'result' => 'pass',
        'reason' => 'Enrollment sukses (samples: ' . count($embeddingsRawBinary) . ')',
        'liveness_passed' => true,
        'liveness_score' => $livenessScore,
        'device_id' => $device['device_id'] ?? null,
        'user_agent' => $device['user_agent'] ?? null,
        'latitude' => $geo['latitude'] ?? null,
        'longitude'=> $geo['longitude'] ?? null,
        'accuracy_m'=> $geo['accuracy_m'] ?? null,
      ]);

      return $profile;
    });
  }
}