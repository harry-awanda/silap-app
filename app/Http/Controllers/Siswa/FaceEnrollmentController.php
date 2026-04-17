<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\Face\{EnrollStartRequest, EnrollSubmitRequest};
use App\Models\{FaceProfile, Siswa};
use App\Services\Face\FaceEnrollmentService;
use App\Services\GeoFenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FaceEnrollmentController extends Controller {

  public function __construct(
    private FaceEnrollmentService $service,
    private GeoFenceService $geo,
  ) {}

  public function status() {
    $siswa = Siswa::where('user_id', auth()->id())->firstOrFail();

    $profile = FaceProfile::query()
      ->where('siswa_id', $siswa->id)
      ->where('is_active', true)
      ->latest('id')
      ->first();

    return response()->json([
      'ok'              => true,
      'has_profile'     => (bool) $profile,
      'profile_id'      => $profile?->id,
      'embeddings_count'=> $profile ? $profile->embeddings()->count() : 0,
    ]);
  }

  public function start(EnrollStartRequest $request) {
    $siswa = Siswa::where('user_id', $request->user()->id)->firstOrFail();

    $termId = (int) ($request->attributes->get('activeTermId') ?? 0);
    abort_if(!$termId, 500, 'Term aktif belum diset.');

    // ✅ kelas dari pivot term
    $classroomId = (int) DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('siswa_id', $siswa->id)
      ->where('status', 'active')
      ->value('classroom_id');

    abort_if(!$classroomId, 403, 'Anda belum terdaftar pada kelas di term aktif.');

    // ✅ geofence (wajib di sekolah untuk enrollment)
    $ok = $this->geo->validateSchool(
      (float) $request->latitude,
      (float) $request->longitude,
      $request->accuracy !== null ? (float) $request->accuracy : null,
      $reason
    );

    if (!$ok) {
      throw ValidationException::withMessages(['lokasi' => $reason]);
    }

    $geo = [
      'latitude'  => (float) $request->latitude,
      'longitude' => (float) $request->longitude,
      'accuracy_m'=> $request->accuracy !== null ? (int) $request->accuracy : null,
    ];

    $device = [
      'device_id' => $request->device_id,
      'user_agent'=> substr(($request->user_agent ?? $request->userAgent() ?? ''), 0, 255),
    ];

    $payload = $this->service->startSession($request->user()->id, $geo, $device);

    return response()->json([
      'ok'      => true,
      'message' => 'Sesi enrollment dibuat.',
      ...$payload,
    ]);
  }
  
  public function submit(EnrollSubmitRequest $request) {
    $siswa = Siswa::where('user_id', $request->user()->id)->firstOrFail();
  
    $termId = (int) ($request->attributes->get('activeTermId') ?? 0);
    abort_if(!$termId, 500, 'Term aktif belum diset.');
  
    $classroomId = (int) DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('siswa_id', $siswa->id)
      ->where('status', 'active')
      ->value('classroom_id');
  
    abort_if(!$classroomId, 403, 'Anda belum terdaftar pada kelas di term aktif.');
  
    // ✅ consume session (wajib valid)
    $this->service->consumeSession($request->session_id, $request->user()->id);
  
    // ✅ geofence ulang
    $ok = $this->geo->validateSchool(
      (float) $request->latitude,
      (float) $request->longitude,
      $request->accuracy !== null ? (float) $request->accuracy : null,
      $reason
    );
    if (!$ok) {
      throw ValidationException::withMessages(['lokasi' => $reason]);
    }
  
    // ✅ Normalisasi embeddings: single atau multi
    $b64List = $request->filled('embeddings_b64')
      ? (array) $request->embeddings_b64
      : [(string) $request->embedding_b64];
  
    $rawList = [];
    foreach ($b64List as $b64) {
      $raw = base64_decode((string) $b64, true);
      if ($raw === false) {
        throw ValidationException::withMessages(['embedding_b64' => 'Embedding tidak valid (base64).']);
      }
      // Float32Array(128) => 512 bytes
      if (strlen($raw) !== 128 * 4) {
        throw ValidationException::withMessages(['embedding_b64' => 'Ukuran embedding tidak valid. Pastikan descriptor Float32Array(128).']);
      }
      $rawList[] = $raw;
    }
  
    $geo = [
      'latitude'  => (float) $request->latitude,
      'longitude' => (float) $request->longitude,
      'accuracy_m'=> $request->accuracy !== null ? (int) $request->accuracy : null,
    ];
  
    $device = [
      'device_id' => $request->device_id,
      'user_agent'=> substr(($request->user_agent ?? $request->userAgent() ?? ''), 0, 255),
    ];
  
    $profile = $this->service->submitEnrollmentMulti(
      siswa: $siswa,
      termId: $termId,
      classroomId: $classroomId,
      geo: $geo,
      device: $device,
      livenessPassed: (bool) $request->liveness_passed,
      livenessScore: $request->liveness_score !== null ? (float) $request->liveness_score : null,
      embeddingsRawBinary: $rawList,
      modelVersion: $request->model_version ?? 'v1',
      meta: $request->meta ?? null,
      actorUserId: $request->user()->id,
    );
  
    return response()->json([
      'ok'              => true,
      'message'         => 'Enrollment wajah berhasil.',
      'profile_id'      => $profile->id,
      'embeddings_saved'=> count($rawList),
    ]);
  }
}