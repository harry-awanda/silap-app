<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider {
  /**
   * Register any application services.
  */
  public function register(): void {
    foreach (glob(app_path('Helpers/*.php')) as $file) {
      require_once $file;
    }
  }
  
  /**
   * Bootstrap any application services.
  */
  public function boot(): void {
    // Force HTTPS bila FORCE_HTTPS=true di .env
    if (env('FORCE_HTTPS', false)) {
      URL::forceScheme('https');
    }
    
    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // (opsional) limiter khusus presensi
    // RateLimiter::for('attendance', function (Request $request) {
    //   return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
    // });
    
    // Rate limit untuk presensi (tanpa ketergantungan IP)
    RateLimiter::for('attendance', function (Request $request) {
      // 1) Login: kunci per user ID
      if ($request->user()) {
        return [
          Limit::perMinute(6)->by('att:user:' . $request->user()->id),
        ];
      }
      
      // 2) Ada session: kunci per session ID
      $sessionId = $request->session()?->getId();
      if (!empty($sessionId)) {
        return [
          Limit::perMinute(6)->by('att:sess:' . $sessionId),
        ];
      }
  
      // 3) Benar-benar anonim: fingerprint ringan TANPA IP
      //    (gunakan info yang "biasa" ada di request)
      $fingerprint = substr(hash('sha256', implode('|', [
        (string) $request->userAgent(),
        (string) $request->path(),
        (string) $request->headers->get('accept-language', ''),
      ])), 0, 32);
  
      return [
        // Lebih ketat untuk anonim agar aman dari abuse
        Limit::perMinute(3)->by('att:anon:' . $fingerprint)
          ->response(function () {
            return new Response('Terlalu banyak percobaan. Coba lagi sebentar.', 429);
          }),
      ];
    });
  }
}