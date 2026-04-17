<?php

namespace App\Services\Face;

use App\Models\{FaceProfile, FaceEmbedding};

class FaceVerifyService {

  public function __construct(private FaceCryptoService $crypto) {}

  /**
   * Ambil similarity terbaik (0..1) dari embedding live terhadap semua embedding tersimpan.
   */
  public function bestSimilarity(FaceProfile $profile, string $liveRawBinary, string $modelVersion = 'v1'): ?float {
    $liveVec = $this->binaryFloat32ToVector($liveRawBinary, 128);
    if (!$liveVec) return null;

    $liveNorm = $this->norm($liveVec);
    if ($liveNorm <= 0) return null;

    $rows = FaceEmbedding::query()
      ->where('face_profile_id', $profile->id)
      ->where('model_version', $modelVersion)
      ->get(['embedding']);

    if ($rows->isEmpty()) return null;

    $best = null;

    foreach ($rows as $row) {
      // embedding tersimpan = string terenkripsi (hasil Crypt::encryptString) disimpan di BLOB
      $encrypted = (string) $row->embedding;

      // decrypt -> raw binary float32 (512 bytes)
      $storedRaw = $this->crypto->decryptEmbedding($encrypted);
      if ($storedRaw === '' || strlen($storedRaw) < 128 * 4) continue;

      $storedVec = $this->binaryFloat32ToVector($storedRaw, 128);
      if (!$storedVec) continue;

      $sim = $this->cosine($liveVec, $storedVec, $liveNorm);
      if ($sim === null) continue;

      $best = ($best === null) ? $sim : max($best, $sim);
    }

    return $best;
  }

  /**
   * Parse binary float32 (little-endian) -> array<float>
   * 128 float32 = 512 bytes.
   */
  private function binaryFloat32ToVector(string $raw, int $expectedLen): ?array {
    if (strlen($raw) < ($expectedLen * 4)) return null;

    // 'g' = float 32-bit little-endian
    $arr = @unpack('g' . $expectedLen, substr($raw, 0, $expectedLen * 4));
    if (!$arr || count($arr) !== $expectedLen) return null;

    return array_values($arr); // unpack 1-indexed -> 0-indexed
  }

  private function cosine(array $a, array $b, float $normA): ?float {
    $n = count($a);
    if ($n !== count($b) || $n === 0) return null;

    $dot = 0.0;
    $normB2 = 0.0;

    for ($i = 0; $i < $n; $i++) {
      $ai = (float) $a[$i];
      $bi = (float) $b[$i];
      $dot += $ai * $bi;
      $normB2 += $bi * $bi;
    }

    $normB = sqrt($normB2);
    if ($normB <= 0) return null;

    $sim = $dot / ($normA * $normB);

    // clamp floating error
    if ($sim < -1) $sim = -1;
    if ($sim > 1) $sim = 1;

    return $sim;
  }

  private function norm(array $v): float {
    $sum = 0.0;
    foreach ($v as $x) {
      $fx = (float) $x;
      $sum += $fx * $fx;
    }
    return sqrt($sum);
  }
}