<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilSekolah extends Model {
  protected $table = 'profil_sekolah';
  
  protected $fillable = [
    'nama_sekolah', 'alamat', 'nomor_telepon', 'email', 'npsn', 'kepala_sekolah_id', 'kesiswaan_id'
  ];
  
  public function kepalaSekolah() {
    return $this->belongsTo(Guru::class, 'kepala_sekolah_id');
  }
  
  public function kesiswaan() {
    return $this->belongsTo(Guru::class, 'kesiswaan_id');
  }
}
