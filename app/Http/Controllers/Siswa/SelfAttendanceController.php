<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Attendance, Siswa};
use App\Services\GeoFenceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SelfAttendanceController extends Controller {

  public function __construct(private GeoFenceService $geo) {}

  public function form(Request $request) {
    $cfg = config('presensi');

    return view('siswa.presensi', [
      'title' => 'Presensi Kehadiran',
      'school' => $cfg['school'],
      'maxAccuracy' => $cfg['max_accuracy_m'],
      'formAction' => route('presensi.store'),
      'buttonLabel' => 'Absen Hadir',
      'introText' => 'Presensi mandiri hanya bisa dari area sekolah.',
      'mode' => 'present',
    ]);
  }

  public function lateForm(Request $request, string $token) {
    $this->assertLateQrToken($token);

    $cfg = config('presensi');

    return view('siswa.presensi', [
      'title' => 'Presensi Terlambat',
      'school' => $cfg['school'],
      'maxAccuracy' => $cfg['max_accuracy_m'],
      'formAction' => route('presensi.late.store', ['token' => $token]),
      'buttonLabel' => 'Absen Terlambat',
      'introText' => 'Presensi ini khusus siswa terlambat melalui QR guru piket.',
      'mode' => 'late',
    ]);
  }

  public function precheck(Request $request) {
    $data = $request->validate([
      'latitude'  => 'required|numeric|between:-90,90',
      'longitude' => 'required|numeric|between:-180,180',
      'accuracy'  => 'nullable|numeric|min:0',
    ]);

    $ok = $this->geo->validateSchool(
      (float) $data['latitude'],
      (float) $data['longitude'],
      isset($data['accuracy']) ? (float) $data['accuracy'] : null,
      $reason
    );

    $key = 'att:precheck:' . ($request->user()->id);
    Cache::put($key, [
      'lat'      => (float) $data['latitude'],
      'lng'      => (float) $data['longitude'],
      'accuracy' => $data['accuracy'] ?? null,
      'ok'       => $ok,
      'reason'   => $reason,
    ], now()->addSeconds(config('presensi.precheck_ttl_seconds', 120)));

    return response()->json([
      'ok'      => $ok,
      'message' => $ok ? 'Siap untuk presensi.' : $reason,
    ], $ok ? 200 : 422);
  }

  public function store(Request $request) {
    return $this->recordAttendance(
      request: $request,
      status: 'hadir',
      source: 'self',
      notes: 'Presensi mandiri',
      successMessage: 'Presensi berhasil.'
    );
  }

  public function lateStore(Request $request, string $token) {
    $this->assertLateQrToken($token);

    return $this->recordAttendance(
      request: $request,
      status: 'terlambat',
      source: 'late_qr',
      notes: 'Presensi terlambat via QR guru piket',
      successMessage: 'Presensi terlambat berhasil.'
    );
  }

  private function recordAttendance(Request $request, string $status, string $source, string $notes, string $successMessage) {
    $siswa = Siswa::where('user_id', $request->user()->id)->firstOrFail();

    $termId = (int) ($request->attributes->get('activeTermId') ?? 0);
    abort_if(!$termId, 500, 'Term aktif belum diset.');

    $data = $request->validate([
      'latitude'   => 'required|numeric|between:-90,90',
      'longitude'  => 'required|numeric|between:-180,180',
      'accuracy'   => 'nullable|numeric|min:0',
      'user_agent' => 'nullable|string|max:255',
    ]);

    $classroomId = (int) DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('siswa_id', $siswa->id)
      ->where('status', 'active')
      ->value('classroom_id');

    abort_if(!$classroomId, 403, 'Anda belum terdaftar pada kelas di term aktif.');

    if (!$this->geo->validateSchool(
      (float) $data['latitude'],
      (float) $data['longitude'],
      isset($data['accuracy']) ? (float) $data['accuracy'] : null,
      $reason
    )) {
      throw ValidationException::withMessages(['lokasi' => $reason]);
    }

    $this->guardSpeed($request, (float) $data['latitude'], (float) $data['longitude']);

    $now = Carbon::now();

    return DB::transaction(function () use ($request, $siswa, $data, $now, $status, $source, $notes, $successMessage, $termId, $classroomId) {
      $today = $now->toDateString();

      $existing = Attendance::query()
        ->where('term_id', $termId)
        ->where('siswa_id', $siswa->id)
        ->whereDate('date', $today)
        ->first();

      if ($existing) {
        if ((int) $existing->classroom_id !== $classroomId) {
          abort(409, 'Presensi hari ini sudah tercatat pada kelas lain. Hubungi admin untuk koreksi data.');
        }

        if (in_array($existing->status, ['izin','sakit','alpa'], true)
          && !config('presensi.allow_override_guru_status')) {
          abort(409, 'Presensi hari ini sudah dicatat oleh guru sebagai ' . $existing->status . '.');
        }

        abort(409, 'Presensi hari ini sudah ada.');
      }

      $att = Attendance::create([
        'term_id'      => $termId,
        'siswa_id'     => $siswa->id,
        'classroom_id' => $classroomId,
        'date'         => $today,
        'time'         => $now->format('H:i:s'),
        'status'       => $status,
        'latitude'     => $data['latitude'],
        'longitude'    => $data['longitude'],
        'accuracy_m'   => $data['accuracy'] ?? null,
        'source'       => $source,
        'notes'        => $notes,
        'user_agent'   => substr(($data['user_agent'] ?? $request->userAgent() ?? ''), 0, 255),
      ]);

      return response()->json([
        'ok'      => true,
        'message' => $successMessage,
        'status'  => $att->status,
        'time'    => $att->time,
      ]);
    });
  }

  private function assertLateQrToken(string $token): void {
    $expected = trim((string) config('presensi.late_qr_secret', ''));

    abort_if($expected === '' || !hash_equals($expected, trim($token)), 403, 'QR presensi terlambat tidak valid.');
  }

  private function guardSpeed(Request $request, float $lat, float $lng): void {
    $key  = 'att:last:' . $request->user()->id;
    $prev = Cache::get($key);
    Cache::put($key, ['t' => now()->timestamp, 'lat' => $lat, 'lng' => $lng], now()->addMinutes(10));

    if ($prev && isset($prev['t'], $prev['lat'], $prev['lng'])) {
      $dtSec    = max(1, now()->timestamp - (int) $prev['t']);
      $distM    = $this->geo->haversineMeters($lat, $lng, (float) $prev['lat'], (float) $prev['lng']);
      $speedKmh = ($distM / 1000) / ($dtSec / 3600);

      if ($speedKmh > (int) config('presensi.max_speed_kmh', 150)) {
        abort(422, 'Deteksi pergerakan tidak wajar. Coba ulangi di lokasi stabil.');
      }
    }
  }
}