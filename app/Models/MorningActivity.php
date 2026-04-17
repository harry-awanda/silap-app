<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MorningActivity extends Model {
  protected $fillable = ['kode','nama','weekday','active'];

  public function attendances() {
    return $this->hasMany(MorningActivityAttendance::class);
  }
}
