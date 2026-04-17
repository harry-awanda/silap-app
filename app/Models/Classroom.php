<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveTerm;

class Classroom extends Model {
  use BelongsToActiveTerm;

  protected $fillable = ['term_id', 'nama_kelas', 'tingkat'];

  public function scopeOrdered($q) {
    return $q->orderBy('tingkat')->orderBy('nama_kelas');
  }

  public function term() {
    return $this->belongsTo(AcademicTerm::class, 'term_id');
  }

  public function siswa() {
    return $this->hasMany(Siswa::class, 'classroom_id');
  }

  public function siswaTerms() {
    return $this->belongsToMany(
      Siswa::class,
      'term_classroom_siswa',
      'classroom_id',
      'siswa_id'
    )
      ->withPivot(['term_id', 'status'])
      ->withTimestamps();
  }

  // helper query: “siswa di kelas ini pada term tertentu”
  public function siswaByTerm($termId) {
    return $this->siswaTerms()->wherePivot('term_id', $termId);
  }

  // Semua riwayat penugasan wali kelas untuk kelas ini (per term)
  public function homeroomAssignments() {
    return $this->hasMany(HomeroomAssignment::class, 'classroom_id');
  }

  // Penugasan wali kelas yang sedang aktif pada term & periode berjalan
  public function currentHomeroom() {
    return $this->hasOne(HomeroomAssignment::class, 'classroom_id')
      ->whereNull('ended_at') // aktif
      ->latestOfMany('started_at'); // jika ada beberapa, ambil paling baru
  }

  // Helper akses cepat ke model Guru wali saat ini
  public function getCurrentWaliGuruAttribute() {
    return $this->currentHomeroom?->guru;
  }

  // Relasi ke attendance lewat siswa
  public function attendances() {
    return $this->hasManyThrough(
      Attendance::class, // model tujuan
      Siswa::class,      // model perantara
      'classroom_id',    // FK di tabel siswa
      'siswa_id',        // FK di tabel attendance
      'id',              // PK di tabel classroom
      'id'               // PK di tabel siswa
    );
  }
}
