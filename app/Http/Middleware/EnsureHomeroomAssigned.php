<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\HomeroomAssignment;
use App\Support\ActiveTermCache;

class EnsureHomeroomAssigned {
  public function handle(Request $request, Closure $next): Response {
    $user = $request->user();
    if (!$user) abort(401);

    // ✅ hanya berlaku untuk wali_kelas
    if (!$user->hasRole('wali_kelas')) {
      return $next($request);
    }

    $activeTermId = (int) ($request->attributes->get('activeTermId') ?: ActiveTermCache::activeTermId());

    // ✅ kalau belum ada term aktif, jangan 503
    if (!$activeTermId) {
      return redirect()
        ->route('dashboard') // atau halaman admin setting term aktif
        ->with('warning', 'Term aktif belum disetel. Hubungi admin untuk menyetel Tahun Ajaran/Semester aktif.');
    }

    $assignment = HomeroomAssignment::query()
      ->select(['id', 'guru_id', 'classroom_id'])
      ->where('term_id', $activeTermId)
      ->whereNull('ended_at')
      ->whereHas('guru', fn($q) => $q->where('user_id', $user->id))
      ->first();

    if (!$assignment) {
      abort(403, 'Anda bukan wali kelas pada term aktif.');
    }

    $request->attributes->set('homeroom_assignment_id', $assignment->id);
    return $next($request);
  }
}
