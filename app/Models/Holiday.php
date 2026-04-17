<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Holiday extends Model {
  protected $fillable = ['name', 'start_date', 'end_date', 'is_active'];

  protected $casts = [
    'start_date' => 'date',
    'end_date'   => 'date',
    'is_active'  => 'boolean',
  ];

  public function scopeActive(Builder $q): Builder {
    return $q->where('is_active', true);
  }
}
