<?php

namespace App\Services\Face;

use App\Models\{FaceProfile, Siswa};
use Illuminate\Support\Facades\DB;

class FaceProfileService {
  public function activeProfileFor(Siswa $siswa): ?FaceProfile {
    return FaceProfile::query()
      ->where('siswa_id', $siswa->id)
      ->where('is_active', true)
      ->latest('id')
      ->first();
  }

  public function deactivateActiveProfiles(Siswa $siswa, ?int $actorUserId = null): void {
    FaceProfile::query()
      ->where('siswa_id', $siswa->id)
      ->where('is_active', true)
      ->update(['is_active' => false, 'created_by' => $actorUserId]);
  }

  public function createActiveProfile(Siswa $siswa, ?int $actorUserId = null): FaceProfile {
    return FaceProfile::create([
      'siswa_id'   => $siswa->id,
      'is_active'  => true,
      'created_by' => $actorUserId,
    ]);
  }
}
