<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\BelongsToActiveTerm;

class HomeroomAssignment extends Model {
  use BelongsToActiveTerm;
  
  protected $fillable = [
    'term_id','guru_id','classroom_id',
    'assigned_at','started_at','ended_at'
  ];

  protected $casts = [
    'assigned_at' => 'datetime',
    'started_at'  => 'datetime',
    'ended_at'    => 'datetime',
  ];

  // --- Relations ---
  public function term() {
    return $this->belongsTo(AcademicTerm::class, 'term_id');
  }

  public function guru() {
    return $this->belongsTo(Guru::class, 'guru_id');
  }
  
  public function classroom() {
    return $this->belongsTo(Classroom::class, 'classroom_id');
  }

  // --- Scopes ---
  public function scopeActive(Builder $q): Builder {
    return $q->whereNull('ended_at');
  }

  // --- Helpers ---
  public function endNow(): void {
    $this->ended_at = now();
    $this->save();
  }

  /**
   * Assign (aktifkan) wali kelas dengan aman:
   * - Akhiri assignment aktif lama untuk kelas & guru pada term yang sama (jika ada)
   * - Buat assignment baru (aktif)
   */
  public static function assignSafely(int $termId, int $classroomId, int $guruId, ?\DateTimeInterface $startAt = null): self {
    return DB::transaction(function () use ($termId, $classroomId, $guruId, $startAt) {
      // Akhiri assignment aktif lain di term yang sama (kelas ini)
      static::forTerm($termId)->where('classroom_id', $classroomId)->active()->update(['ended_at' => now()]);
      // Akhiri assignment aktif lain di term yang sama (guru ini)
      static::forTerm($termId)->where('guru_id', $guruId)->active()->update(['ended_at' => now()]);

      return static::create([
        'term_id'      => $termId,
        'classroom_id' => $classroomId,
        'guru_id'      => $guruId,
        'assigned_at'  => now(),
        'started_at'   => $startAt ?? now(),
        'ended_at'     => null,
      ]);
    });
  }
}