<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveTerm;

class Attendance extends Model {

	use BelongsToActiveTerm;
	
  protected $fillable = ['term_id','siswa_id','classroom_id','date','time','status',
  'latitude','longitude','accuracy_m',
  'source','notes','user_agent'];
  
  protected $casts = [
    'date' => 'date',
  ];

  public function isLate(): bool {
    return $this->status === 'terlambat';
  }
  
  public function siswa() {
		return $this->belongsTo(Siswa::class, 'siswa_id');
	}
	public function classroom() {
		return $this->belongsTo(Classroom::class);
	}
}