<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveTerm;

class JadwalPiket extends Model {
  use BelongsToActiveTerm;

  protected $table = 'jadwal_piket';
  // Kolom yang dapat diisi secara massal
  protected $fillable = [
    'term_id',
    'guru_id',
    'hari_piket',
  ];

  // Mendefinisikan hubungan dengan model Guru
  public function guru() {
    return $this->belongsTo(Guru::class, 'guru_id');
  }
}