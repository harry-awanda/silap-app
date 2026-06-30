<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable {

  use HasRoles, HasFactory, Notifiable, CanResetPassword;
  
  protected $guard_name = 'web';
  protected $fillable = [ 'name', 'username', 'email', 'password', 'must_change_password', 'password_changed_at', 'disabled_at',];
  protected $hidden = [ 'password', 'remember_token', ];

  // Relasi one-to-one dengan model Guru
  public function guru() {
    return $this->hasOne(Guru::class);
  }
  
  public function siswa() {
    return $this->hasOne(Siswa::class, 'user_id');
  }

  protected function casts(): array {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  /**
   * Semua assignment wali kelas milik user (lewat tabel guru)
   */
  public function homeroomAssignments() {
    return $this->hasManyThrough(
      HomeroomAssignment::class, // target
      Guru::class,               // perantara
      'user_id',                 // FK di guru yang mengarah ke users.id
      'guru_id',                 // FK di homeroom_assignments yang mengarah ke guru.id
      'id',                      // PK users
      'id'                       // PK guru
    );
  }

  /**
   * Assignment wali kelas user untuk term tertentu (yang aktif)
   */
  public function homeroomOnTerm(int $termId) {
    return $this->hasOneThrough(
      HomeroomAssignment::class,
      Guru::class,
      'user_id',
      'guru_id',
      'id',
      'id'
    )
      ->withoutActiveTerm()
      ->forTerm($termId)
      ->whereNull('ended_at')
      ->latestOfMany('started_at');
  }
}