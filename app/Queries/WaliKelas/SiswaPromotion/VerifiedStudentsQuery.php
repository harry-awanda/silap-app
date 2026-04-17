<?php

namespace App\Queries\WaliKelas\SiswaPromotion;

use App\Models\Siswa;
use App\Support\HomeroomContext;

class VerifiedStudentsQuery {
  public function get(array $ids, int $termId, int $classroomId) {
    $binaanIds = HomeroomContext::siswaIdsInClassTerm($termId, $classroomId, 'active')
      ->map(fn($v) => (int) $v)
      ->all();

    $binaanSet = array_flip($binaanIds);

    foreach ($ids as $sid) {
      abort_if(!isset($binaanSet[(int) $sid]), 403, 'Ada siswa yang bukan binaan.');
    }

    $siswa = Siswa::whereIn('id', $ids)
      ->orderBy('nama_lengkap')
      ->get(['id', 'nis', 'nama_lengkap', 'jenis_kelamin']);

    abort_if($siswa->count() !== count($ids), 403, 'Ada siswa yang bukan binaan.');
    return $siswa;
  }
}