<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OutPassStudent extends Model {
  use HasFactory;

  // Status pakai string juga
  public const STATUSES = ['approved_out','returned','not_returning','canceled'];

  protected $fillable = [
    'out_pass_id', 'siswa_id',
    'time_out', 'time_back', 'status',
    'handled_by_id', 'remarks',
    'active_guard',
  ];

  protected $casts = [
    'time_out' => 'datetime',
    'time_back'=> 'datetime',
  ];

  /* Relationships */
  public function outPass()    { return $this->belongsTo(OutPass::class); }
  public function siswa()      { return $this->belongsTo(Siswa::class, 'siswa_id'); }
  public function handledBy()  { return $this->belongsTo(Guru::class, 'handled_by_id'); }

  /* Helpers */
  public function getIsActiveAttribute(): bool {
    return $this->status === 'approved_out'
      && is_null($this->time_back)
      && (bool) optional($this->outPass)->return_expected;
  }

  /* Scopes */
  public function scopeActive($q)      { return $q->where('status','approved_out')->whereNull('time_back'); }
  public function scopeReturned($q)    { return $q->where('status','returned')->whereNotNull('time_back'); }
  public function scopeNotReturning($q){ return $q->where('status','not_returning'); }
}