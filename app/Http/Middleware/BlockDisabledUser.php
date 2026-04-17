<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockDisabledUser {
  public function handle(Request $request, Closure $next) {
    $user = $request->user();

    if ($user && $user->disabled_at !== null) {

      // Logout user alumni
      Auth::logout();

      // invalidate session
      $request->session()->invalidate();
      $request->session()->regenerateToken();

      return redirect()
        ->route('auth.login')
        ->with('error', 'Akun Anda sudah tidak aktif. Terima kasih sudah menjadi bagian dari sekolah kami.');
    }

    return $next($request);
  }
}