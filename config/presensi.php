<?php

return [

  /*
  |--------------------------------------------------------------------------
  | School Geofence
  |--------------------------------------------------------------------------
  | Koordinat sekolah dan radius geofence (meter).
  */
  'school' => [
    'lat' => (float) env('SCHOOL_LAT', 0.9087880935810898),
    'lng' => (float) env('SCHOOL_LNG', 104.54397372883564),
    'radius_m' => (int) env('SCHOOL_GEOFENCE_M', 250),
  ],

  /*
  |--------------------------------------------------------------------------
  | (DEPRECATED) Allowed IP Ranges
  |--------------------------------------------------------------------------
  | Tidak digunakan lagi sejak migrasi SSO/2FA. Biarkan kosong.
  */
  'allowed_ip_ranges' => [],

  /*
  |--------------------------------------------------------------------------
  | Cutoff Time (Asia/Jakarta)
  |--------------------------------------------------------------------------
  | Batas waktu keterlambatan harian siswa.
  */
  'cutoff_time' => env('SCHOOL_CUTOFF', '07:30'),

  /*
  |--------------------------------------------------------------------------
  | Late Attendance Static QR
  |--------------------------------------------------------------------------
  | Secret URL token untuk QR statis presensi terlambat yang didampingi guru piket.
  */
  'late_qr_secret' => env('LATE_ATTENDANCE_QR_SECRET'),

  /*
  |--------------------------------------------------------------------------
  | GPS Validation
  |--------------------------------------------------------------------------
  */
  'max_accuracy_m' => (int) env('GPS_MAX_ACCURACY_M', 200),

  /*
  |--------------------------------------------------------------------------
  | Anti Fake GPS Heuristic
  |--------------------------------------------------------------------------
  */
  'max_speed_kmh' => (int) env('GPS_MAX_SPEED_KMH', 150),

  /*
  |--------------------------------------------------------------------------
  | Precheck Caching
  |--------------------------------------------------------------------------
  | TTL (detik) untuk caching precheck lokasi agar hemat request.
  */
  'precheck_ttl_seconds' => (int) env('PRECHECK_TTL', 120),

  /*
  |--------------------------------------------------------------------------
  | Override Status Guru
  |--------------------------------------------------------------------------
  */
  'allow_override_guru_status' => (bool) env('ALLOW_OVERRIDE_GURU_STATUS', false),

];
