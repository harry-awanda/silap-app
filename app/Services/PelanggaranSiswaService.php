<?php

namespace App\Services;

use App\Models\PelanggaranSiswa;

class PelanggaranSiswaService {
  public function create(array $payload, array $pelanggaranIds, int $termId): PelanggaranSiswa {
    $rec = PelanggaranSiswa::create($payload + ['term_id' => $termId]);

    $pivot = [];
    foreach ($pelanggaranIds as $pid) {
      $pivot[$pid] = ['term_id' => $termId];
    }
    $rec->pelanggaran()->attach($pivot);

    return $rec;
  }

  public function update(PelanggaranSiswa $rec, array $update, array $pelanggaranIds, int $termId): PelanggaranSiswa {
    $rec->update($update + (empty($rec->term_id) ? ['term_id' => $termId] : []));

    $pivot = [];
    foreach ($pelanggaranIds as $pid) {
      $pivot[$pid] = ['term_id' => $termId];
    }
    $rec->pelanggaran()->sync($pivot);

    return $rec;
  }
}