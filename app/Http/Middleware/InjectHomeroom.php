<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\HomeroomAssignment;

class InjectHomeroom {
  public function handle(Request $request, Closure $next)
  {
    $id = $request->attributes->get('homeroom_assignment_id');
    $homeroom = $id
      ? HomeroomAssignment::with(['guru.user','classroom.siswa'])->find($id)
      : null;

    $request->attributes->set('homeroom', $homeroom);
    view()->share('homeroom', $homeroom); // supaya bisa dipakai di Blade
    view()->share('isWaliKelasActive', (bool)$homeroom);

    return $next($request);
  }
}
