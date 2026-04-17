<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\HomeroomContext;

class Guru extends Model {

  protected $table = 'guru';
  protected $fillable = [
    'user_id', 'nip','nama_lengkap','tempat_lahir','tanggal_lahir','jenis_kelamin', 'alamat', 'kontak', 'photo',
  ];

  public function user() {
    return $this->belongsTo(User::class);
  }
  
  public function homeroomAssignments() {
    return $this->hasMany(HomeroomAssignment::class, 'guru_id');
  }
  
  public function homeroomOnTerm(int $termId) {
    return $this->hasOne(HomeroomAssignment::class, 'guru_id')
      ->withoutActiveTerm()
      ->forTerm($termId)
      ->whereNull('ended_at')
      ->latestOfMany('started_at');
  }
  
  public function currentHomeroom() {
    return $this->homeroomOnTerm(HomeroomContext::activeTermId());
  }
  
  public function jadwalPiket() {
    return $this->hasOne(JadwalPiket::class, 'guru_id');
  }

  // Cascade delete user ketika teacher dihapus
  protected static function booted() {
    static::deleting(function ($guru) {
      $guru->user()->delete();
    });
  }
}