<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange {

  public function handle(Request $request, Closure $next) {
    if (auth()->check()) {
      $user = auth()->user();
      
      // Rute yang tetap boleh diakses agar tidak loop
      $allowed = $request->routeIs([
        'password.first.change',
        'password.first.update',
        'logout',
      ]);

      if ($user->must_change_password && !$allowed) {
        return redirect()->route('password.first.change')
        ->with('warning', 'Silakan ganti password terlebih dahulu.');
      }
    }
    return $next($request);
  }
}