<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Classroom;
use App\Models\HomeroomAssignment;

class ClassroomPolicy {
  /**
   * Pastikan user adalah wali kelas untuk classroom ini pada term aktif.
   */
  public function waliKelas(User $user, Classroom $classroom): bool
  {
    if (!$user->guru_id) return false;

    return HomeroomAssignment::where('guru_id', $user->guru_id)
      ->where('classroom_id', $classroom->id)
      ->exists(); // trait BelongsToActiveTerm akan menscope ke term aktif
  }
}
