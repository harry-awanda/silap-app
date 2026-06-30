<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\View;
use App\Support\ActiveTermCache;

class InjectActiveTerm {

  public function handle(Request $request, Closure $next): Response {
    $activeTerm = ActiveTermCache::rememberActiveTerm();
    
    // $activeTerm = Cache::remember($cacheKey, 60, function () {
    //   // 1) Prioritas: is_active = 1, ambil yang paling “baru” berdasar start_date lalu id
    //   $term = AcademicTerm::query()
    //   ->where('is_active', true)
    //   ->orderByDesc('start_date')
    //   ->orderByDesc('id')
    //   ->first();
      
    //   if ($term) return $term;
      
    //   // 2) Fallback: term terbaru (kalau belum ada yang diaktifkan)
    //   return AcademicTerm::query()
    //   ->orderByDesc('start_date')
    //   ->orderByDesc('id')
    //   ->first();
    // });

    // Inject ke request
    $request->attributes->set('activeTerm', $activeTerm);
    $request->attributes->set('activeTermId', $activeTerm?->id);
    
    // Share ke Blade (untuk semua view)
    View::share('activeTerm', $activeTerm);
    View::share('activeTermId', $activeTerm?->id);
    
    $response = $next($request);
    
    // Tambahkan header untuk debugging/API client
    if ($activeTerm) {
      // Kamu sudah punya kolom "name" → pakai ini sebagai label utama
      $label = $activeTerm->name;
      $response->headers->set('X-Active-Term-Id', (string) $activeTerm->id);
      $response->headers->set('X-Active-Term', $label);
      // (Opsional) info kunci tambahan berguna untuk mobile/log
      $response->headers->set('X-Active-Term-Semester', (string) $activeTerm->semester); // ganjil|genap
      $response->headers->set('X-Active-Term-Years', $activeTerm->year_start.'/'.$activeTerm->year_end);
    } else {
      $response->headers->set('X-Active-Term-Id', '');
      $response->headers->set('X-Active-Term', '');
    }
    
    return $response;
  }
}
