<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveTerm;

class PelanggaranSiswa extends Model {
  
  use BelongsToActiveTerm;
  
  protected $table = 'pelanggaran_siswa';
  protected $fillable = [
    'term_id',
    'siswa_id',
    'tanggal_pelanggaran',
    'keterangan',
    'status',
    'tindakan',
    'catatan_waliKelas',
    'catatan_kesiswaan',
    'catatan_guruBK'
  ];
  
  public function siswa() {
    return $this->belongsTo(Siswa::class);
  }

  public function pelanggaran() {
    return $this->belongsToMany(
      Pelanggaran::class,
      'pelanggaran_siswa_pelanggaran',
      'pelanggaran_siswa_id',
      'pelanggaran_id'
    )
    ->withPivot(['term_id'])
    ->withTimestamps();
  }
  
  public function dataPelanggaran() {
    return $this->pelanggaran();
  }
}