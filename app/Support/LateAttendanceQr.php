<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Str;

class LateAttendanceQr {
  public const SETTING_KEY = 'late_attendance_qr_secret';

  public static function secret(): string {
    return trim((string) AppSetting::getValue(self::SETTING_KEY, ''));
  }

  public static function generate(): string {
    $secret = Str::random(64);
    AppSetting::setValue(self::SETTING_KEY, $secret);

    return $secret;
  }

  public static function url(): ?string {
    $secret = self::secret();

    return $secret !== ''
      ? route('presensi.late.form', ['token' => $secret])
      : null;
  }

  public static function isConfiguredFromDatabase(): bool {
    return trim((string) AppSetting::getValue(self::SETTING_KEY, '')) !== '';
  }

  public static function matches(string $token): bool {
    $expected = self::secret();

    return $expected !== '' && hash_equals($expected, trim($token));
  }
}
