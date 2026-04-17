<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\WaliKelas\SiswaPromotion\PreviewRequest;
use App\Http\Requests\WaliKelas\SiswaPromotion\CommitRequest;

use App\Models\{Classroom, AcademicTerm};
use App\Models\HomeroomAssignment;

use App\Support\HomeroomContext;
use App\Services\WaliKelas\SiswaPromotionService;
use App\Queries\WaliKelas\SiswaPromotion\CandidateToTermsQuery;
use App\Queries\WaliKelas\SiswaPromotion\TargetClassesQuery;
use App\Queries\WaliKelas\SiswaPromotion\SiswaBinaanByTermQuery;

class SiswaPromotionController extends Controller {

  private const MODES = ['promote', 'graduate'];

  public function __construct(
    private SiswaPromotionService $service,
    private CandidateToTermsQuery $toTerms,
    private TargetClassesQuery $targetClasses,
    private SiswaBinaanByTermQuery $siswaBinaan
  ) {}

  /* =====================================================
   * INDEX
   * ===================================================== */
  public function index(Request $request, string $mode) {
    $this->validateMode($mode);

    $title   = $mode === 'promote' ? 'Naik Kelas' : 'Kelulusan Siswa';
    $current = $this->homeroomClassroom($request);
    $fromTerm = $this->activeTerm($request);

    // ambil siswa binaan (term aktif, status active)
    $siswa = $this->siswaBinaan->get($current, (int) $fromTerm->id);

    $toTerms = collect();
    $toTerm  = null;
    $targetClasses = collect();
    
    if ($mode === 'promote') {
      // kelas 12 semester GENAP -> wajib graduate
      if ((int) $current->tingkat === 12 && $this->service->isGenap($fromTerm)) {
        return redirect()->route('siswa.promosi.index', 'graduate');
      }
    
      $toTerms = $this->toTerms->get($fromTerm);
    
      // default to_term_id: query -> first term
      $qToTermId = (int) $request->query('to_term_id', (int) ($toTerms->first()?->id ?? 0));
    
      $toTerm = $this->service->resolveToTerm($toTerms, $qToTermId);
    
      // jenis promosi: advance|repeat (default advance)
      $kind = strtolower((string) $request->query('promote_kind', 'advance'));
      if (!in_array($kind, ['advance', 'repeat'], true)) $kind = 'advance';
    
      if ($toTerm) {
        $targetTingkat = $this->service->expectedTargetTingkatByKind($fromTerm, $toTerm, $current, $kind);
    
        $targetClasses = $this->targetClasses->get((int) $toTerm->id, $targetTingkat);
    
        // keep-selected target_classid jika masih valid setelah reload
        $qTargetClassId = (int) $request->query('target_classid', 0);
        if ($qTargetClassId) {
          $exists = $targetClasses->firstWhere('id', $qTargetClassId);
          if (!$exists) {
            // kalau yang dipilih tidak ada di list baru, "drop" selection
            $request->query->remove('target_classid');
          }
        }
      } else {
        $targetClasses = collect();
      }
    }

    // kelas 10/11 tidak boleh masuk graduate
    if ((int) $current->tingkat < 12 && $mode === 'graduate') {
      return redirect()->route('siswa.promosi.index', 'promote');
    }

    return view('wali-kelas.siswa.promote.index', [
      'title'         => $title,
      'mode'          => $mode,
      'siswa'         => $siswa,
      'current'       => $current,
      'targetClasses' => $targetClasses,
      'fromTermLabel' => $this->service->termLabel($fromTerm),
      'toTermLabel'   => $toTerm ? $this->service->termLabel($toTerm) : null,
      'toTerms'       => $toTerms,
      'toTerm'        => $toTerm,
    ]);
  }

  /* =====================================================
   * PREVIEW
   * ===================================================== */
  public function preview(PreviewRequest $request, string $mode) {
    $this->validateMode($mode);

    $current  = $this->homeroomClassroom($request);
    $fromTerm = $this->activeTerm($request);
    
    $kind = strtolower((string) $request->input('promote_kind', 'advance'));
    if (!in_array($kind, ['advance', 'repeat'], true)) $kind = 'advance';

    $dto = $this->service->buildPreview(
      mode: $mode,
      fromTerm: $fromTerm,
      current: $current,
      siswaIds: $request->siswa_ids,
      toTermId: $request->input('to_term_id'),
      targetClassId: $request->input('target_classid'),
      angkatan: $request->input('angkatan'),
      promoteKind: $kind
    );

    return view('wali-kelas.siswa.promote.preview', [
      'title'         => 'Preview',
      'mode'          => $mode,
      'siswa'         => $dto['siswa'],
      'current'       => $current,
      'payload'       => $dto['payload'],
      'fromTermLabel' => $this->service->termLabel($fromTerm),
      'toTermLabel'   => $dto['toTerm'] ? $this->service->termLabel($dto['toTerm']) : null,
      'targetClass'   => $dto['targetClass'],
      'promoteKind' => $kind,
    ]);
  }

  /* =====================================================
   * COMMIT
   * ===================================================== */
  public function commit(CommitRequest $request, string $mode) {
    $this->validateMode($mode);

    $current  = $this->homeroomClassroom($request);
    $fromTerm = $this->activeTerm($request);

    $this->service->commit(
      mode: $mode,
      fromTerm: $fromTerm,
      current: $current,
      payload: $request->payload,
      confirm: $request->confirm
    );

    return redirect()->route('siswa.index')->with(
      'success',
      $mode === 'promote'
        ? 'Siswa berhasil diproses untuk term tujuan.'
        : 'Siswa berhasil diluluskan.'
    );
  }

  /* =====================================================
   * Helper dasar (tetap di controller: ctx request)
   * ===================================================== */
  private function validateMode(string $mode): void {
    abort_unless(in_array($mode, self::MODES, true), 404);
  }

  private function activeTerm(Request $request): AcademicTerm {
    $termId = HomeroomContext::activeTermId($request);
    $term = AcademicTerm::find($termId);

    abort_if(!$term, 500, 'Active term belum tersedia.');
    return $term;
  }

  private function homeroomClassroom(Request $request): Classroom {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom || !$homeroom->classroom, 403, 'Anda belum memiliki kelas binaan.');
    return $homeroom->classroom;
  }
}