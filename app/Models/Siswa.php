<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\{
  BelongsTo,
  BelongsToMany,
  HasMany
};

class Siswa extends Model {
  protected $table = 'siswa';

  protected $fillable = [
    // RELASI AKTIF (shortcut)
    'classroom_id', 'user_id',

    // IDENTITAS
    'nis', 'nama_lengkap', 'jenis_kelamin', 'tempat_lahir',
    'tanggal_lahir', 'agama', 'alamat', 'kontak', 'photo',

    // ORANG TUA / WALI
    'nama_ayah', 'pekerjaan_ayah', 'kontak_ayah', 'nama_ibu',
    'pekerjaan_ibu', 'kontak_ibu', 'nama_wali_murid',
    'kontak_wali', 'alamat_orangtua', 'alamat_wali',
  ];

  protected $casts = [
    'tanggal_lahir' => 'date',
  ];

  /* =====================================================
   | BOOT
   ===================================================== */
  protected static function booted() {
    static::deleting(function (self $siswa) {

      // 1. Pelanggaran + pivot
      $siswa->pelanggaranSiswa()->each(function ($ps) {
        $ps->dataPelanggaran()->detach();
        $ps->delete();
      });

      // 2. Attendance & aktivitas pagi
      $siswa->attendances()->delete();
      $siswa->activityAttendances()->delete();

      // 3. Pivot term_classroom_siswa
      \DB::table('term_classroom_siswa')
        ->where('siswa_id', $siswa->id)
        ->delete();

      // 4. File foto
      if ($siswa->photo && Storage::disk('public')->exists($siswa->photo)) {
        Storage::disk('public')->delete($siswa->photo);
      }
    });
  }

  /* =====================================================
   | RELATIONS – CORE
   ===================================================== */

  public function user(): BelongsTo {
    return $this->belongsTo(User::class);
  }

  /**
   * Shortcut kelas AKTIF (cache / UI cepat).
   * Sumber kebenaran tetap pivot.
   */
  public function classroom(): BelongsTo {
    return $this->belongsTo(Classroom::class);
  }

  /* =====================================================
   | RELATIONS – TERM AWARE (PIVOT RESMI)
   ===================================================== */

  /**
   * Semua histori penempatan siswa per term
   */
  public function terms(): BelongsToMany {
    return $this->belongsToMany(
      AcademicTerm::class,
      'term_classroom_siswa',
      'siswa_id',
      'term_id'
    )
      ->withPivot(['classroom_id', 'status'])
      ->withTimestamps();
  }

  /**
   * Semua kelas siswa lintas term
   */
  public function classroomsByTerm(): BelongsToMany {
    return $this->belongsToMany(
      Classroom::class,
      'term_classroom_siswa',
      'siswa_id',
      'classroom_id'
    )
      ->withPivot(['term_id', 'status'])
      ->withTimestamps();
  }

  /**
   * Helper: placement siswa pada term tertentu
   */
  public function placementForTerm(int $termId) {
    return $this->classroomsByTerm()
      ->wherePivot('term_id', $termId)
      ->first();
  }

  /* =====================================================
   | RELATIONS – OPERASIONAL
   ===================================================== */

  public function attendances(): HasMany {
    return $this->hasMany(Attendance::class, 'siswa_id');
  }

  public function pelanggaranSiswa(): HasMany {
    return $this->hasMany(PelanggaranSiswa::class, 'siswa_id');
  }

  public function activityAttendances(): HasMany {
    return $this->hasMany(MorningActivityAttendance::class, 'siswa_id');
  }
}