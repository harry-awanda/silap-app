<?php

namespace App\Support;

use App\Models\AcademicTerm;
use Illuminate\Support\Facades\Cache;

class ActiveTermCache {
  public const TERM_KEY = 'active_term.v1';
  public const ID_KEY = 'active_term_id.v1';
  public const LEGACY_ID_KEY = 'active_term_id';

  public static function rememberActiveTerm(): ?AcademicTerm {
    return Cache::remember(self::TERM_KEY, 60, function () {
      return AcademicTerm::query()
        ->where('is_active', true)
        ->orderByDesc('start_date')
        ->orderByDesc('id')
        ->first();
    });
  }

  public static function activeTermId(): ?int {
    $term = self::rememberActiveTerm();
    if (!$term) return null;

    Cache::put(self::ID_KEY, (int) $term->id, 60);
    Cache::put(self::LEGACY_ID_KEY, (int) $term->id, 60);

    return (int) $term->id;
  }

  public static function forget(): void {
    Cache::forget(self::TERM_KEY);
    Cache::forget(self::ID_KEY);
    Cache::forget(self::LEGACY_ID_KEY);
  }
}
