<?php

namespace App\Support;

use Illuminate\Http\Request;

class ActiveTerm {
  public static function id(Request $request): int {
    $id = $request->attributes->get('activeTermId')
      ?: session('active_term_id');

    abort_unless($id, 422, 'Tahun ajaran belum diaktifkan.');
    return (int) $id;
  }
}