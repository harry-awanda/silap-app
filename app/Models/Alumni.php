<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumni extends Model {
  protected $table = 'alumni';

  protected $fillable = [
    // pakai id siswa (shared PK)
    'id', 'nis', 'nama_lengkap', 'jenis_kelamin',
    'angkatan', 'graduated_at',
  ];

  protected $casts = [
    'graduated_at' => 'datetime',
  ];

  public $incrementing = false; // karena id diisi manual
  protected $keyType = 'int';
}
