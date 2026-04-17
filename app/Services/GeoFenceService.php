<?php

namespace App\Services;

class GeoFenceService {

  public function validateSchool(float $lat, float $lng, ?float $accuracy, ?string &$reason): bool {
    $school = config('presensi.school');
    $radius = (int) ($school['radius_m'] ?? 0);
    $maxAcc = (int) config('presensi.max_accuracy_m', 50);

    if (!$school || !isset($school['lat'], $school['lng']) || $radius <= 0) {
      $reason = 'Konfigurasi lokasi sekolah belum lengkap.';
      return false;
    }

    if ($accuracy !== null && $accuracy > $maxAcc) {
      $reason = "Akurasi GPS kurang baik (>{$maxAcc} m). Pindah lokasi terbuka/Wi-Fi sekolah.";
      return false;
    }

    $distance = $this->haversineMeters($lat, $lng, (float) $school['lat'], (float) $school['lng']);
    if ($distance > $radius) {
      $reason = "Di luar area sekolah (jarak ±" . round($distance) . " m; batas {$radius} m).";
      return false;
    }

    $reason = '';
    return true;
  }

  public function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R    = 6371000; // meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a    = sin($dLat / 2) ** 2
      + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * asin(sqrt($a));
    return $R * $c;
  }
}