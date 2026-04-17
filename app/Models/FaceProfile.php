<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaceProfile extends Model {
  use SoftDeletes;

  protected $fillable = [
    'siswa_id',
    'is_active',
    'created_by',
  ];

  protected $casts = [
    'is_active' => 'boolean',
  ];

  public function siswa() {
    return $this->belongsTo(Siswa::class, 'siswa_id');
  }

  public function embeddings() {
    return $this->hasMany(FaceEmbedding::class, 'face_profile_id');
  }

  public function creator() {
    return $this->belongsTo(User::class, 'created_by');
  }
}