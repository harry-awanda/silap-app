<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceEmbedding extends Model {
  protected $fillable = [
    'face_profile_id',
    'embedding',
    'model_version',
    'meta',
  ];

  protected $casts = [
    'meta' => 'array',
  ];

  public function profile() {
    return $this->belongsTo(FaceProfile::class, 'face_profile_id');
  }
}