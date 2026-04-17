<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceFaceLog extends Model {
  protected $fillable = [
    'term_id',
    'siswa_id',
    'classroom_id',
    'date',
    'time',
    'result',
    'reason',
    'similarity',
    'liveness_passed',
    'liveness_score',
    'device_id',
    'user_agent',
    'latitude',
    'longitude',
    'accuracy_m',
  ];

  protected $casts = [
    'date'           => 'date',
    'liveness_passed'=> 'boolean',
    'similarity'     => 'decimal:3',
    'liveness_score' => 'decimal:3',
  ];

  public function siswa() {
    return $this->belongsTo(Siswa::class, 'siswa_id');
  }

  public function term() {
    return $this->belongsTo(AcademicTerm::class, 'term_id');
  }

  public function classroom() {
    return $this->belongsTo(Classroom::class, 'classroom_id');
  }
}