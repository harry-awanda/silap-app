<?php

return [

  /*
  |--------------------------------------------------------------------------
  | School Geofence
  |--------------------------------------------------------------------------
  | Koordinat sekolah dan radius geofence (meter).

  Home: 0.919389, 104.518120

  School: 0.9087880935810898, 104.54397372883564

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
  | Batas waktu keterlambatan harian siswa (mis. presensi masuk > 07:30 = terlambat).
  */
  'cutoff_time' => env('SCHOOL_CUTOFF', '07:30'),

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

  /*
  |--------------------------------------------------------------------------
  | AUTO ALPA (tanpa scheduler)
  |--------------------------------------------------------------------------
  | - enabled       : matikan/aktifkan fitur.
  | - run_cutoff    : jam eksekusi logis; jika sekarang < jam ini,
  |                   target tanggal bergeser ke hari sekolah sebelumnya.
  | - active_days   : ISO (1=Senin ... 7=Minggu). Default: Senin-Jumat.
  | - default_time  : wajib karena kolom time non-nullable.
  */
//   'auto_alpa' => [
//     'enabled'     => (bool) env('AUTO_ALPA_ENABLED', true),
//     'run_cutoff'  => env('AUTO_ALPA_CUTOFF', '15:00'),
//     'active_days' => [1, 2, 3, 4, 5],
//     'default_time' => env('AUTO_ALPA_TIME', '00:00:00')
//   ],

];
