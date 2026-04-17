<?php

namespace App\Support;

use App\Models\Guru;
use App\Models\HomeroomAssignment;
use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeroomContext {
  /**
   * Ambil activeTermId yang konsisten:
   * 1) request attribute 'activeTermId' (dari middleware InjectActiveTerm)
   * 2) session('active_term_id') jika dipakai
   * 3) cache 'active_term.v1' jika kamu menyimpannya (optional)
   * 4) fallback: DB academic_terms where is_active = 1
   */
  
  public static function activeTermId(?Request $request = null): ?int {
    $req = $request ?: request();

    // 1) request attribute
    if ($req && $id = $req->attributes->get('activeTermId')) {
      return (int) $id;
    }

    // 2) session fallback
    if ($id = session('active_term_id')) {
      return (int) $id;
    }

    // 3) cache fallback (optional, mengikuti trait BelongsToActiveTerm)
    $cached = Cache::get('active_term.v1');
    if (is_object($cached) && isset($cached->id)) return (int) $cached->id;
    if (is_array($cached) && isset($cached['id'])) return (int) $cached['id'];

    // 4) DB fallback
    $id = AcademicTerm::query()->where('is_active', true)->value('id');
    if (!$id) {
      return null; // jangan abort
    }
    return (int) $id;
  }

  /**
   * Ambil guru dari user login.
   */
  public static function authedGuru(): ?Guru {
    $userId = auth()->id();
    if (!$userId) return null;

    return Guru::query()
      ->where('user_id', $userId)
      ->first();
  }

  /**
   * Ambil assignment wali_kelas aktif untuk guru login (term aktif).
   * Return: [assignment|null, classroomId|null, guru|null]
   */
  public static function forAuthed(?Request $request = null): array {
    $guru = self::authedGuru();
    if (!$guru) {
      return [null, null, null];
    }

    $termId = self::activeTermId($request);

    if (!$termId) {
      return [null, null, $guru];
    }
    
    $assignment = HomeroomAssignment::query()
      ->where('guru_id', $guru->id)
      ->where('term_id', $termId)
      ->whereNull('ended_at')
      ->latest('started_at')
      ->first();

    return [$assignment, $assignment?->classroom_id, $guru];
  }

  /**
   * Pastikan user login adalah wali kelas pada term aktif.
   */
  public static function ensureHasHomeroom(?Request $request = null): void {
    [$asgn] = self::forAuthed($request);
    abort_if(!$asgn, 403, 'Anda tidak memiliki penugasan wali kelas aktif pada term berjalan.');
  }

  /* ============================================================
   | TERM-CLASSROOM-SISWA (PIVOT) helpers  ==> inti "kelas per term"
   ============================================================ */

  /**
   * Apakah siswa berada pada classroom tertentu di term tertentu (pivot)?
   */
  public static function siswaInClassTerm(int $termId, int $classroomId, int $siswaId, string $status = 'active'): bool {
    return DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('classroom_id', $classroomId)
      ->where('siswa_id', $siswaId)
      ->when($status !== '*', fn($q) => $q->where('status', $status))
      ->exists();
  }

  /**
   * Ambil daftar siswa_id untuk kelas tertentu pada term tertentu (pivot).
   */
  public static function siswaIdsInClassTerm(int $termId, int $classroomId, string $status = 'active') {
    return DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('classroom_id', $classroomId)
      ->when($status !== '*', fn($q) => $q->where('status', $status))
      ->pluck('siswa_id');
  }

  /**
   * Abort jika siswa bukan binaan wali pada term aktif.
   * (Dipakai di controller WaliKelas agar 100% term-aware)
   */
  public static function assertSiswaBinaanTerm(int $termId, int $classroomId, int $siswaId): void {
    abort_if(
      !self::siswaInClassTerm($termId, $classroomId, $siswaId, 'active'),
      403,
      'Bukan siswa binaan Anda pada term aktif.'
    );
  }
}