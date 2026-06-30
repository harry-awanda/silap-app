<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\HomeroomAssignment;
use App\Support\ActiveTermCache;
use Spatie\Permission\Models\Role;

class SyncWaliKelasRole {

  public function handle(Request $request, Closure $next) {
    $user = $request->user();
    
    if (!$user) return $next($request);
    
    $activeTermId = $request->attributes->get('activeTermId') ?: ActiveTermCache::activeTermId();
    $guruId = optional($user->guru)->id;
    
    // Deteksi apakah user adalah wali kelas pada term aktif
    $isWali = $guruId && $activeTermId
      ? HomeroomAssignment::withoutActiveTerm()
        ->where('guru_id', $guruId)
        ->where('term_id', $activeTermId)
        ->whereNull('ended_at')
        ->exists()
      : false;

    $hasWaliHistory = $guruId
      ? HomeroomAssignment::withoutActiveTerm()->where('guru_id', $guruId)->exists()
      : false;
        
      // Pastikan role 'wali_kelas' tersedia di tabel roles
      Role::firstOrCreate(['name' => 'wali_kelas']);
      
    // Sinkronkan role wali_kelas
    if (($isWali || $hasWaliHistory) && !$user->hasRole('wali_kelas')) {
      $user->assignRole('wali_kelas');
    } elseif (!$isWali && !$hasWaliHistory && $user->hasRole('wali_kelas')) {
      $user->removeRole('wali_kelas');
    }
    return $next($request);
  }
}
