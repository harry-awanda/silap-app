<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ConfigController extends Controller {
  /**
   * Ambil konfigurasi global presensi (cutoff, lokasi sekolah, batas akurasi).
   */
  public function presensi() {
    $cfg = config('presensi');

    return response()->json([
      'school' => [
        'lat' => $cfg['school']['lat'],
        'lng' => $cfg['school']['lng'],
        'radius_m' => $cfg['school']['radius_m'],
      ],
      'cutoff' => $cfg['cutoff'],
      'max_accuracy_m' => $cfg['max_accuracy_m'],
      'timezone' => $cfg['timezone'] ?? 'Asia/Jakarta',
    ]);
  }
}