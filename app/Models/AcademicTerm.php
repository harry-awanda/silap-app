<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model {
  protected $fillable = [
    'name',
    'year_start', 'year_end',
    'semester',          // 'ganjil' | 'genap'
    'start_date', 'end_date',
    'is_active',
    'lock_attendance_at',
    'lock_violation_at',
  ];
    
  protected $casts = [
    'is_active'          => 'boolean',
    'start_date'         => 'date',
    'end_date'           => 'date',
    'lock_attendance_at' => 'datetime',
    'lock_violation_at'  => 'datetime',
  ];
}