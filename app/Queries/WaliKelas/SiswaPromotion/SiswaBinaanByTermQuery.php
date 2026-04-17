<?php

namespace App\Queries\WaliKelas\SiswaPromotion;

use App\Models\Classroom;

class SiswaBinaanByTermQuery {
  public function get(Classroom $classroom, int $termId) {
    return $classroom->siswaByTerm($termId)
      ->wherePivot('status', 'active')
      ->select('siswa.id', 'siswa.nis', 'siswa.nama_lengkap', 'siswa.jenis_kelamin')
      ->orderBy('siswa.nama_lengkap')
      ->get();
  }
}