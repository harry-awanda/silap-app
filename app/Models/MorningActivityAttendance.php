<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveTerm;

class MorningActivityAttendance extends Model {
  use BelongsToActiveTerm;

  protected $fillable = [
    'term_id','siswa_id','classroom_id','morning_activity_id',
    'custom_activity_name',
    'tanggal','status','keterangan'
  ];

  public function siswa() {
    return $this->belongsTo(Siswa::class); 
  }

  public function classroom() {
    return $this->belongsTo(Classroom::class);
  }

  public function activity() {
    return $this->belongsTo(MorningActivity::class, 'morning_activity_id');
  }
}