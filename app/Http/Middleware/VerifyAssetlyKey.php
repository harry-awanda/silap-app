<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyAssetlyKey {
  public function handle(Request $request, Closure $next) {
    $incomingKey = trim((string) $request->header('X-ASSETLY-KEY'));

    // ✅ ini yang benar untuk SILAP
    $expectedKey = trim((string) config('services.assetly.key'));

    if (!$incomingKey || !$expectedKey || !hash_equals($expectedKey, $incomingKey)) {
      return response()->json([
        'ok' => false,
        'error' => 'UNAUTHORIZED_APP',
      ], 403);
    }

    return $next($request);
  }
}
