<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

// Class untuk mengekspor rekap bulanan absensi ke Excel
class MonthlyRecapExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize {

  // Variabel untuk menyimpan data rekap absensi dan daftar siswa
  protected $rekapAbsensi;
  protected Collection $siswa;

  // Konstruktor untuk menginisialisasi variabel dengan data yang diterima dari controller
  public function __construct($rekapAbsensi, $siswa) {
    $this->rekapAbsensi = $rekapAbsensi; // Data rekap absensi yang sudah diproses sebelumnya
    // Daftar siswa yang ada di kelas binaan guru
    $this->siswa = $siswa instanceof Collection ? $siswa : collect($siswa);
  }

  // Fungsi untuk mengembalikan koleksi data siswa yang akan diekspor
  public function collection() {
    return $this->siswa; // Mengembalikan koleksi siswa yang akan diekspor
  }
  // Fungsi untuk mengatur judul kolom pada file Excel
  public function headings(): array {
    return [
      'Nama Lengkap', // Kolom untuk nama lengkap siswa
      'NIS',
      'Sakit',        // Kolom untuk jumlah hari sakit
      'Izin',         // Kolom untuk jumlah hari izin
      'Alpa',         // Kolom untuk jumlah hari alpa
    ];
  }

  // Fungsi untuk memetakan data siswa dengan rekap absensi masing-masing
  public function map($siswa): array {
    $id = (int) ($siswa->id ?? 0);
    
    // aman untuk array / collection
    $sakit = data_get($this->rekapAbsensi, "{$id}.sakit", 0);
    $izin  = data_get($this->rekapAbsensi, "{$id}.izin", 0);
    $alpa  = data_get($this->rekapAbsensi, "{$id}.alpa", 0);
    
    return [
      $siswa->nama_lengkap ?? '-',
      $siswa->nis ?? '-',
      (int) $sakit,
      (int) $izin,
      (int) $alpa,
    ];
  }
}