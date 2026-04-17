<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class SchoolCalendarService {
  public function isSchoolDay(Carbon $date): bool {
    $active = config('presensi.auto_alpa.active_days', [1,2,3,4,5]);
    $isActiveIso = in_array($date->dayOfWeekIso, $active, true);
    if (!$isActiveIso) return false;

    $isHoliday = Holiday::active()
      ->whereDate('start_date', '<=', $date)
      ->whereDate('end_date', '>=', $date)
      ->exists();

    return !$isHoliday;
  }

  public function previousSchoolDay(Carbon $from): Carbon {
    $d = $from->copy()->subDay();
    while (!$this->isSchoolDay($d)) {
      $d->subDay();
    }
    return $d;
  }
}
