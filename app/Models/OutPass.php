<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveTerm;

class OutPass extends Model {
  use BelongsToActiveTerm;

  // Master list (string, bukan enum)
  public const REASONS = [
    'membeli_barang' => 'Membeli Barang',
    'sakit_pulang'   => 'Sakit Pulang',
    'kegiatan_luar'  => 'Kegiatan Luar',
    'lainnya'        => 'Lainnya',
  ];

  public const METHODS = [
    'telepon'  => 'Telepon',
    'whatsapp' => 'WhatsApp',
    'lisan'    => 'Lisan',
  ];

  protected $fillable = [
    'term_id','classroom_id', 'picket_teacher_id',
    'destination', 'reason',
    'approved_by_id', 'approved_by_name', 'approval_method', 'approval_at',
    'time_out', 'return_expected', 'notes',
    'created_by', 'updated_by',
  ];

  protected $casts = [
    'time_out'        => 'datetime',
    'approval_at'     => 'datetime',
    'return_expected' => 'boolean',
  ];

  /* Relationships */
  public function classroom() {
    return $this->belongsTo(Classroom::class);
  }

  public function picketTeacher() {
    return $this->belongsTo(Guru::class, 'picket_teacher_id'); 
  }

  public function approvedBy() {
    return $this->belongsTo(Guru::class, 'approved_by_id');
  }
  
  public function details() {
    return $this->hasMany(OutPassStudent::class);
  }

  // Many-to-many style accessor (opsional)
  public function students() {
    return $this->belongsToMany(Siswa::class, 'out_pass_students', 'out_pass_id', 'siswa_id')
    ->withPivot(['time_out','time_back','status','handled_by_id','remarks'])
    ->withTimestamps();
  }

  /* Scopes */
  public function scopeOfClassrooms($q, array|int $classroomIds) {
    return $q->whereIn('classroom_id', (array)$classroomIds); 
  }

  public function scopeDate($q, $date) {
    return $q->whereDate('time_out', $date);
  }
  
  public function scopeRange($q, $start, $end) {
    return $q->whereBetween('time_out', [$start, $end]);
  }
}