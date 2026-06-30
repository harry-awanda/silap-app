<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

// use App\Http\Middleware\CheckRole;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\TrustProxies as AppTrustProxies;
use App\Http\Middleware\InjectActiveTerm;
use App\Http\Middleware\EnsureHomeroomAssigned;
use App\Http\Middleware\InjectHomeroom;
use App\Http\Middleware\SyncWaliKelasRole;
use App\Http\Middleware\BlockDisabledUser;
use App\Http\Middleware\VerifyAssetlyKey;
// use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    // 0) Pastikan di-stack paling awal supaya semua downstream bisa akses activeTerm
    $middleware->prepend(InjectActiveTerm::class);    // ⬅️ GLOBAL: berlaku untuk web & api

    $middleware->alias([
      // 'checkRole' => CheckRole::class,
      'role'            => RoleMiddleware::class,
      'syncWaliRole' => SyncWaliKelasRole::class,
      'ensure.homeroom' => EnsureHomeroomAssigned::class,
      'inject.homeroom' => InjectHomeroom::class,
      'force.password.change' => ForcePasswordChange::class,
      'block.disabled' => BlockDisabledUser::class,
      'assetly.key' => VerifyAssetlyKey::class,
    ]);
    
    // aktifkan TrustProxies (yang baca TRUST_MODE/ENV)
    $middleware->append(AppTrustProxies::class);
    
    $middleware->group('protected', [
      'auth',
      'force.password.change',
      'block.disabled',
    ]);
    
  })
  ->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (TokenMismatchException $e, $request) {
      // Redirect ke halaman login
      return redirect()
      ->route('login')
      ->with('warning', 'Sesi Anda telah habis, silakan login kembali.');
    });
  })->create();
