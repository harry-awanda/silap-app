<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AcademicTerm;
use App\Models\HomeroomAssignment;
use Spatie\Permission\Models\Role;

class SyncWaliKelasRole {

  public function handle(Request $request, Closure $next) {
    $user = $request->user();
    
    if (!$user) return $next($request);
    
    // Ambil term aktif
    $activeTermId = AcademicTerm::query()->where('is_active', true)->value('id');
    if (!$activeTermId) return $next($request);
    
    // Deteksi apakah user adalah wali kelas pada term aktif
    $isWali = $user->relationLoaded('guru')
      ? optional($user->guru)->exists &&
      HomeroomAssignment::where('guru_id', $user->guru->id)
        ->where('term_id', $activeTermId)
        ->whereNull('ended_at')
        ->exists()
      : ($user->guru && HomeroomAssignment::where('guru_id', $user->guru->id)
        ->where('term_id', $activeTermId)
        ->whereNull('ended_at')
        ->exists());
        
      // Pastikan role 'wali_kelas' tersedia di tabel roles
      Role::firstOrCreate(['name' => 'wali_kelas']);
      
    // Sinkronkan role wali_kelas
    if ($isWali && !$user->hasRole('wali_kelas')) {
      $user->assignRole('wali_kelas');
    } elseif (!$isWali && $user->hasRole('wali_kelas')) {
      $user->removeRole('wali_kelas');
    }
    return $next($request);
  }
}