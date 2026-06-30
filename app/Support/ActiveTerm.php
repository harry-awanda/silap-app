<?php

namespace App\Support;

use Illuminate\Http\Request;

class ActiveTerm {
  public static function id(Request $request): int {
    // 1) Dari middleware (InjectActiveTerm)
    $id = $request->attributes->get('activeTermId');

    // 2) Dari session (mis. user pernah pilih term)
    if (!$id) {
      $id = session('active_term_id');
    }

    // 3) Fallback: term yang ditandai aktif di DB (lintas role)
    if (!$id) {
      $id = ActiveTermCache::activeTermId();
    }

    abort_unless($id, 422, 'Tahun ajaran belum diaktifkan.');
    return (int) $id;
  }
}
