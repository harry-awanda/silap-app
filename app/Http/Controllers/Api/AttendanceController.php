<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Attendance;

class AttendanceController extends Controller {
  /**
   * Simpan data presensi mandiri (tanpa QR).
   */
  public function store(Request $request) {
    $data = $request->validate([
      'latitude' => 'required|numeric|between:-90,90',
      'longitude' => 'required|numeric|between:-180,180',
      'accuracy_m' => 'required|numeric|min:0',
      'user_agent' => 'nullable|string|max:255',
      'source' => 'nullable|string|max:50',
    ]);

    $cfg = config('presensi');
    $now = Carbon::now($cfg['timezone'] ?? 'Asia/Jakarta');

    $siswa = $request->user()->siswa;
    if (!$siswa) {
      return response()->json(['message' => 'Data siswa tidak ditemukan'], 422);
    }

    // --- Validasi lokasi (geofence)
    $distance = $this->distance(
      $data['latitude'],
      $data['longitude'],
      $cfg['school']['lat'],
      $cfg['school']['lng']
    );

    if ($distance > $cfg['school']['radius_m']) {
      return response()->json(['message' => 'Di luar area sekolah'], 422);
    }

    // --- Validasi akurasi GPS
    if ($data['accuracy_m'] > $cfg['max_accuracy_m']) {
      return response()->json(['message' => 'Akurasi lokasi terlalu rendah'], 422);
    }

    // --- Tentukan status hadir / terlambat
    $cutoff = Carbon::createFromFormat('H:i', $cfg['cutoff'], $cfg['timezone'] ?? 'Asia/Jakarta');
    $status = $now->lte($cutoff) ? 'hadir' : 'terlambat';

    // --- Simpan ke database
    $att = Attendance::create([
      'siswa_id' => $siswa->id,
      'classroom_id' => $siswa->classroom_id,
      'date' => $now->toDateString(),
      'time' => $now->format('H:i:s'),
      'status' => $status,
      'latitude' => $data['latitude'],
      'longitude' => $data['longitude'],
      'accuracy_m' => $data['accuracy_m'],
      'source' => $data['source'] ?? 'mobile',
      'user_agent' => $data['user_agent'] ?? $request->userAgent(),
    ]);

    return response()->json([
      'id' => $att->id,
      'status' => $status,
      'timestamp' => $now->toIso8601String(),
      'message' => 'Presensi berhasil disimpan'
    ], 201);
  }

  /**
   * Ambil riwayat presensi.
   */
  public function history(Request $request) {
    $siswa = $request->user()->siswa;
    if (!$siswa) {
      return response()->json(['message' => 'Data siswa tidak ditemukan'], 422);
    }

    $query = Attendance::where('siswa_id', $siswa->id)
      ->orderByDesc('date')
      ->limit(30);

    return response()->json($query->get());
  }

  /**
   * Override presensi oleh guru piket / wali kelas / kesiswaan.
   */
  public function override(Request $request) {
    $data = $request->validate([
      'siswa_id' => 'required|integer|exists:siswa,id',
      'date' => 'required|date',
      'status' => 'required|string|in:hadir,terlambat,sakit,izin,alpa',
      'note' => 'nullable|string|max:255',
    ]);

    $att = Attendance::updateOrCreate(
      ['siswa_id' => $data['siswa_id'], 'date' => $data['date']],
      [
        'status' => $data['status'],
        'source' => 'wali_kelas',
        'note' => $data['note'] ?? null,
      ]
    );

    return response()->json([
      'message' => 'Data presensi diperbarui',
      'data' => $att
    ]);
  }

  /**
   * Fungsi helper menghitung jarak antara dua koordinat (meter).
   */
  private function distance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // jari-jari bumi dalam meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
  }
}