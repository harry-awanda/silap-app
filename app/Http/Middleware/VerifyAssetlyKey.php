<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyAssetlyKey {
  public function handle(Request $request, Closure $next) {
    $key = $request->header('X-ASSETLY-KEY');

    if (!$key || $key !== config('services.assetly.key')) {
      return response()->json([
        'ok' => false,
        'error' => 'UNAUTHORIZED_APP',
      ], 403);
    }

    return $next($request);
  }
}