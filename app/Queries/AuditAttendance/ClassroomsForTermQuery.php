<?php

namespace App\Queries\AuditAttendance;

use App\Models\Classroom;

class ClassroomsForTermQuery {
  public function get(int $termId) {
    return Classroom::withoutActiveTerm()
      ->where('term_id', $termId)
      ->orderBy('tingkat')
      ->orderBy('nama_kelas')
      ->get(['id','nama_kelas','tingkat']);
  }
}