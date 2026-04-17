<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Guru;
use App\Models\Concerns\BelongsToActiveTerm;

class AgendaPiket extends Model {
  use BelongsToActiveTerm;

  protected $table = 'agenda_piket';

  protected $fillable = [
    'term_id',
    'tanggal',
    'kejadian_normal',
    'kejadian_masalah',
    'solusi',
    'guru_piket',
    'absensi_per_kelas',
    'absensi_per_tingkat',
  ];

  protected $casts = [
    'guru_piket'          => 'array', // array of guru_id
    'absensi_per_tingkat' => 'array',
    // opsional: kalau butuh
    // 'absensi_per_kelas' => 'array',
  ];

  public function guruKbmAbsences() {
    return $this->hasMany(GuruKbmAbsence::class);
  }

  // Helper: ambil nama guru dari array id
  public function getGuruPiketNames(): array {
    $ids = is_array($this->guru_piket) ? $this->guru_piket : [];
    if (empty($ids)) return [];
    return Guru::whereIn('id', $ids)->orderBy('nama_lengkap')->pluck('nama_lengkap')->toArray();
  }
}
