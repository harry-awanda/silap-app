<?php

namespace App\Queries\WaliKelas\SiswaPromotion;

use App\Models\Classroom;

class TargetClassesQuery {
  public function get(int $toTermId, int $targetTingkat) {
    return Classroom::withoutActiveTerm()
      ->forTerm($toTermId)
      ->where('tingkat', $targetTingkat)
      ->orderBy('nama_kelas')
      ->get();
  }
}