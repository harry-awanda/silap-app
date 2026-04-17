<?php

namespace App\Queries\WaliKelas\SiswaPromotion;

use App\Models\AcademicTerm;

class CandidateToTermsQuery {
  public function get(AcademicTerm $from) {
    $sem = strtolower((string) $from->semester);

    if ($sem === 'ganjil') {
      return AcademicTerm::where('year_start', $from->year_start)
        ->where('year_end',   $from->year_end)
        ->whereRaw('LOWER(semester) = ?', ['genap'])
        ->orderByDesc('id')
        ->get();
    }

    if ($sem === 'genap') {
      return AcademicTerm::where('year_start', $from->year_start + 1)
        ->where('year_end',   $from->year_end + 1)
        ->whereRaw('LOWER(semester) = ?', ['ganjil'])
        ->orderByDesc('id')
        ->get();
    }

    return collect();
  }
}