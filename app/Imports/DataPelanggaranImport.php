<?php

namespace App\Imports;

use App\Models\Pelanggaran;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DataPelanggaranImport implements ToModel, WithHeadingRow {
  public function model(array $row) {
    // Lewati jika kolom kosong
    if (!isset($row['jenis']) || !isset($row['nama']) || empty($row['jenis']) || empty($row['nama'])) {
      return null;
    }
    // Cek apakah kombinasi jenis + nama sudah ada
    $existing = Pelanggaran::where('jenis', $row['jenis'])
      ->where('nama', $row['nama'])
      ->first();

    if ($existing === null) {
      // Insert baru jika belum ada
      return new Pelanggaran([
        'jenis' => $row['jenis'],
        'nama'  => $row['nama'],
      ]);
    }

    // Jika sudah ada, tidak perlu insert ulang
    return null;
  }
}