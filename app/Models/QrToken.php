<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrToken extends Model {
  protected $table = 'qr_tokens';

  protected $fillable = [
    'token','purpose',
    'subject_type','subject_ref',
    'payload',
    'expires_at','used_at','used_count',
    'created_by',
  ];

  protected $casts = [
    'payload' => 'array',
    'expires_at' => 'datetime',
    'used_at' => 'datetime',
  ];

  public function isExpired(): bool {
    return $this->expires_at && $this->expires_at->isPast();
  }

  public function isUsed(): bool {
    return !is_null($this->used_at);
  }
}