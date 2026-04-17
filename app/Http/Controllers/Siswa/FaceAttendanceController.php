<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\Face\FaceAttendanceRequest;
use App\Models\Siswa;
use App\Services\Face\FaceAttendanceService;
use App\Services\GeoFenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FaceAttendanceController extends Controller {

  public function __construct(
    private FaceAttendanceService $service,
    private GeoFenceService $geo,
  ) {}

  public function store(FaceAttendanceRequest $request) {
    $siswa = Siswa::where('user_id', $request->user()->id)->firstOrFail();

    $termId = (int) ($request->attributes->get('activeTermId') ?? 0);
    abort_if(!$termId, 500, 'Term aktif belum diset.');

    $classroomId = (int) DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('siswa_id', $siswa->id)
      ->where('status', 'active')
      ->value('classroom_id');

    abort_if(!$classroomId, 403, 'Anda belum terdaftar pada kelas di term aktif.');

    // ✅ geofence wajib
    $ok = $this->geo->validateSchool(
      (float) $request->latitude,
      (float) $request->longitude,
      $request->accuracy !== null ? (float)$request->accuracy : null,
      $reason
    );

    if (!$ok) {
      throw ValidationException::withMessages(['lokasi' => $reason]);
    }

    $raw = base64_decode($request->embedding_b64, true);
    if ($raw === false) {
      throw ValidationException::withMessages(['embedding_b64' => 'Embedding tidak valid (base64).']);
    }
    
    // Float32Array(128) => 512 bytes
    if (strlen($raw) !== 128 * 4) {
      throw ValidationException::withMessages(['embedding_b64' => 'Ukuran embedding tidak valid. Pastikan descriptor Float32Array(128).']);
    }

    $geo = [
      'latitude'  => (float) $request->latitude,
      'longitude' => (float) $request->longitude,
      'accuracy_m'=> $request->accuracy !== null ? (int)$request->accuracy : null,
    ];

    $device = [
      'device_id' => $request->device_id,
      'user_agent'=> substr(($request->user_agent ?? $request->userAgent() ?? ''), 0, 255),
    ];

    $att = $this->service->submit(
      siswa: $siswa,
      termId: $termId,
      classroomId: $classroomId,
      geo: $geo,
      device: $device,
      livenessPassed: (bool) $request->liveness_passed,
      livenessScore: $request->liveness_score !== null ? (float)$request->liveness_score : null,
      liveRawBinary: $raw,
      modelVersion: $request->model_version ?? 'v1',
    );

    return response()->json([
      'ok'     => true,
      'message'=> $att->status === 'hadir' ? 'Presensi wajah berhasil.' : 'Presensi wajah berhasil (terlambat).',
      'status' => $att->status,
      'time'   => $att->time,
      'sim'    => $att->similarity,
    ]);
  }
}