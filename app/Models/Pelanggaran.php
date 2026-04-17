<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggaran extends Model {
  protected $table = 'pelanggaran';
  protected $fillable = ['jenis','nama'];

  public function pelanggaranSiswa() {
    return $this->belongsToMany(PelanggaranSiswa::class, 'pelanggaran_siswa_pelanggaran', 'pelanggaran_id', 'pelanggaran_siswa_id')->withTimestamps();
  }
  
  // Scope bantu filter
  public function scopeJenis($q, $jenis) {
    return $q->where('jenis', $jenis);
  }
}
