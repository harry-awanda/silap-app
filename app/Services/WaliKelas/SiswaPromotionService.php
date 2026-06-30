<?php

namespace App\Services\WaliKelas;

use Illuminate\Support\Facades\DB;

use App\Models\{Siswa, User, Classroom, AcademicTerm};
use App\Support\HomeroomContext;
use App\Queries\WaliKelas\SiswaPromotion\VerifiedStudentsQuery;

class SiswaPromotionService {
  private const CONFIRM_PROMOTE  = 'PROMOTE';
  private const CONFIRM_GRADUATE = 'LULUS';

  public function __construct(
    private VerifiedStudentsQuery $verifiedStudents
  ) {}

  /* =====================================================
   * Helpers umum
   * ===================================================== */

  public function isGenap(AcademicTerm $t): bool {
    return strtolower((string) $t->semester) === 'genap';
  }

  public function termLabel(AcademicTerm $t): string {
    return "{$t->year_start}/{$t->year_end} - " . ucfirst($t->semester);
  }

  public function isSameAcademicYear(AcademicTerm $a, AcademicTerm $b): bool {
    return (int) $a->year_start === (int) $b->year_start
        && (int) $a->year_end   === (int) $b->year_end;
  }

  /**
   * Hitung target tingkat berdasarkan jenis promosi
   * - advance : naik kelas
   * - repeat  : tinggal kelas
   */
  public function expectedTargetTingkatByKind(
    AcademicTerm $from,
    AcademicTerm $to,
    Classroom $current,
    string $kind
  ): int {
    // Semester ganjil -> genap (tahun ajaran sama): tingkat tetap
    if ($this->isSameAcademicYear($from, $to)) {
      return (int) $current->tingkat;
    }

    // Tahun ajaran baru
    return $kind === 'repeat'
      ? (int) $current->tingkat
      : (int) $current->tingkat + 1;
  }

  public function resolveToTerm($toTerms, int $toTermId): ?AcademicTerm {
    if (!$toTerms || $toTerms->isEmpty()) return null;
    return $toTerms->firstWhere('id', $toTermId) ?? $toTerms->first();
  }

  /* =====================================================
   * Preview Builder
   * ===================================================== */

  public function buildPreview(
    string $mode,
    AcademicTerm $fromTerm,
    Classroom $current,
    array $siswaIds,
    ?int $toTermId = null,
    ?int $targetClassId = null,
    ?string $angkatan = null,
    ?string $promoteKind = null
  ): array {
    // pastikan siswa benar-benar binaan aktif
    $siswa = $this->verifiedStudents->get(
      ids: $siswaIds,
      termId: (int) $fromTerm->id,
      classroomId: (int) $current->id
    );

    $payload = [
      'mode'      => $mode,
      'siswa_ids' => $siswa->pluck('id')->all(),
    ];

    $toTerm = null;
    $targetClass = null;

    if ($mode === 'promote') {
      $toTerm = AcademicTerm::findOrFail((int) $toTermId);
      $targetClass = Classroom::withoutActiveTerm()->findOrFail((int) $targetClassId);

      abort_if(
        (int) $targetClass->term_id !== (int) $toTerm->id,
        403,
        'Kelas tujuan tidak sesuai term tujuan.'
      );

      $kind = $promoteKind ?: 'advance';

      $expectedTingkat = $this->expectedTargetTingkatByKind(
        $fromTerm,
        $toTerm,
        $current,
        $kind
      );

      abort_if(
        (int) $targetClass->tingkat !== $expectedTingkat,
        403,
        'Tingkat kelas tujuan tidak sesuai pilihan promosi.'
      );

      $payload += [
        'from_term_id'   => (int) $fromTerm->id,
        'to_term_id'     => (int) $toTerm->id,
        'target_classid' => (int) $targetClass->id,
        'promote_kind'   => $kind,
      ];
    } else {
      $payload['angkatan'] = (string) $angkatan;
    }

    return [
      'siswa'       => $siswa,
      'payload'     => $payload,
      'toTerm'      => $toTerm,
      'targetClass' => $targetClass,
    ];
  }

  /* =====================================================
   * Commit (Transactional)
   * ===================================================== */

  public function commit(
    string $mode,
    AcademicTerm $fromTerm,
    Classroom $current,
    array $payload,
    string $confirm
  ): void {
    $this->assertConfirm($mode, $confirm);

    $ids = $payload['siswa_ids'] ?? [];
    abort_if(!is_array($ids) || empty($ids), 422, 'Payload siswa tidak valid.');

    $fromSemester = strtolower((string) $fromTerm->semester);

    // Guard kelulusan
    if ($mode === 'graduate') {
      abort_if((int) $current->tingkat !== 12, 403, 'Kelulusan hanya untuk kelas 12.');
      abort_if($fromSemester !== 'genap', 403, 'Kelulusan hanya bisa diproses pada semester genap.');
    }

    // Guard promosi
    if ($mode === 'promote') {
      abort_if(
        (int) $current->tingkat === 12 && $fromSemester === 'genap',
        403,
        'Kelas 12 semester genap harus diproses kelulusan.'
      );
    }

    // pastikan semua siswa binaan
    $this->assertAllBinaan(
      termId: (int) $fromTerm->id,
      classroomId: (int) $current->id,
      ids: $ids
    );

    // validasi ulang target kelas (anti-bypass)
    if ($mode === 'promote') {
      $toTerm = AcademicTerm::findOrFail((int) $payload['to_term_id']);
      $targetClass = Classroom::withoutActiveTerm()->findOrFail((int) $payload['target_classid']);

      $kind = (string) ($payload['promote_kind'] ?? 'advance');

      $expected = $this->expectedTargetTingkatByKind(
        $fromTerm,
        $toTerm,
        $current,
        $kind
      );

      abort_if((int) $targetClass->term_id !== (int) $toTerm->id, 403);
      abort_if((int) $targetClass->tingkat !== $expected, 403);
    }

    DB::transaction(function () use ($mode, $payload, $ids, $current, $fromTerm) {
      if ($mode === 'promote') {
        $this->commitPromote(
          ids: $ids,
          fromTermId: (int) $fromTerm->id,
          fromClassId: (int) $current->id,
          toTermId: (int) $payload['to_term_id'],
          toClassId: (int) $payload['target_classid'],
        );
        return;
      }

      $this->commitGraduate(
        ids: $ids,
      );
    });
  }

  /* =====================================================
   * Internal: Promote
   * ===================================================== */

  private function commitPromote(
    array $ids,
    int $fromTermId,
    int $fromClassId,
    int $toTermId,
    int $toClassId
  ): void {
    
    $targetClass = Classroom::withoutActiveTerm()->findOrFail($toClassId);
    abort_if((int) $targetClass->term_id !== (int) $toTermId, 403, 'Kelas tujuan tidak sesuai term tujuan.');
    // nonaktifkan pivot term asal
    DB::table('term_classroom_siswa')
      ->where('term_id', $fromTermId)
      ->where('classroom_id', $fromClassId)
      ->whereIn('siswa_id', $ids)
      ->update([
        'status'     => 'inactive',
        'updated_at' => now(),
      ]);

    // aktifkan di term tujuan
    foreach ($ids as $sid) {
      DB::table('term_classroom_siswa')->updateOrInsert(
        ['term_id' => $toTermId, 'siswa_id' => (int) $sid],
        [
          'classroom_id' => $toClassId,
          'status'       => 'active',
          'created_at'   => now(),
          'updated_at'   => now(),
        ]
      );
    }
  }

  /* =====================================================
   * Internal: Graduate
   * ===================================================== */

  private function commitGraduate(array $ids): void {
    $students = Siswa::whereIn('id', $ids)->get();

    foreach ($students as $s) {
      if ($s->user_id) {
        User::where('id', $s->user_id)->update(['disabled_at' => now()]);
      }

      $s->delete();
    }
  }

  /* =====================================================
   * Guards
   * ===================================================== */

  private function assertConfirm(string $mode, string $input): void {
    $expected = $mode === 'promote'
      ? self::CONFIRM_PROMOTE
      : self::CONFIRM_GRADUATE;

    abort_if(strtoupper((string) $input) !== $expected, 403);
  }

  private function assertAllBinaan(int $termId, int $classroomId, array $ids): void {
    $binaanIds = HomeroomContext::siswaIdsInClassTerm($termId, $classroomId, 'active')
      ->map(fn ($v) => (int) $v)
      ->all();

    $binaanSet = array_flip($binaanIds);

    foreach ($ids as $sid) {
      abort_if(!isset($binaanSet[(int) $sid]), 403, 'Ada siswa yang bukan binaan.');
    }
  }
}
