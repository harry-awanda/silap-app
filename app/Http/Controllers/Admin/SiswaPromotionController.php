<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\WaliKelas\SiswaPromotion\PreviewRequest;
use App\Http\Requests\WaliKelas\SiswaPromotion\CommitRequest;
use App\Models\{AcademicTerm, Classroom};
use App\Queries\WaliKelas\SiswaPromotion\CandidateToTermsQuery;
use App\Queries\WaliKelas\SiswaPromotion\SiswaBinaanByTermQuery;
use App\Queries\WaliKelas\SiswaPromotion\TargetClassesQuery;
use App\Services\WaliKelas\SiswaPromotionService;
use App\Support\HomeroomContext;

class SiswaPromotionController extends Controller {

  private const MODES = ['promote', 'graduate'];

  public function __construct(
    private SiswaPromotionService $service,
    private CandidateToTermsQuery $toTerms,
    private TargetClassesQuery $targetClasses,
    private SiswaBinaanByTermQuery $siswaBinaan
  ) {}

  public function index(Request $request, string $mode) {
    $this->validateMode($mode);

    $title    = $mode === 'promote' ? 'Naik Kelas' : 'Kelulusan Siswa';
    $fromTerm = $this->activeTerm($request);
    $classes  = $this->sourceClasses($fromTerm, $mode);
    $current  = $this->selectedClassroom($request, $fromTerm, $classes);

    if ((int) $current->tingkat === 12 && $this->service->isGenap($fromTerm) && $mode === 'promote') {
      return redirect()->route('admin.siswa.promosi.index', [
        'mode' => 'graduate',
        'classroom_id' => $current->id,
      ]);
    }

    if ((int) $current->tingkat < 12 && $mode === 'graduate') {
      return redirect()->route('admin.siswa.promosi.index', [
        'mode' => 'promote',
        'classroom_id' => $current->id,
      ]);
    }

    $siswa = $this->siswaBinaan->get($current, (int) $fromTerm->id);

    $toTerms = collect();
    $toTerm  = null;
    $targetClasses = collect();

    if ($mode === 'promote') {
      $toTerms = $this->toTerms->get($fromTerm);
      $qToTermId = (int) $request->query('to_term_id', (int) ($toTerms->first()?->id ?? 0));
      $toTerm = $this->service->resolveToTerm($toTerms, $qToTermId);

      $kind = strtolower((string) $request->query('promote_kind', 'advance'));
      if (!in_array($kind, ['advance', 'repeat'], true)) $kind = 'advance';

      if ($toTerm) {
        $targetTingkat = $this->service->expectedTargetTingkatByKind($fromTerm, $toTerm, $current, $kind);
        $targetClasses = $this->targetClasses->get((int) $toTerm->id, $targetTingkat);

        $qTargetClassId = (int) $request->query('target_classid', 0);
        if ($qTargetClassId && !$targetClasses->firstWhere('id', $qTargetClassId)) {
          $request->query->remove('target_classid');
        }
      }
    }

    return view('wali-kelas.siswa.promote.index', [
      'title'         => $title,
      'mode'          => $mode,
      'siswa'         => $siswa,
      'current'       => $current,
      'classes'       => $classes,
      'targetClasses' => $targetClasses,
      'fromTermLabel' => $this->service->termLabel($fromTerm),
      'toTermLabel'   => $toTerm ? $this->service->termLabel($toTerm) : null,
      'toTerms'       => $toTerms,
      'toTerm'        => $toTerm,
    ]);
  }

  public function preview(PreviewRequest $request, string $mode) {
    $this->validateMode($mode);

    $fromTerm = $this->activeTerm($request);
    $current  = $this->classroomFromInput($request, $fromTerm);

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

    $dto['payload']['classroom_id'] = (int) $current->id;

    return view('wali-kelas.siswa.promote.preview', [
      'title'         => 'Preview',
      'mode'          => $mode,
      'siswa'         => $dto['siswa'],
      'current'       => $current,
      'payload'       => $dto['payload'],
      'fromTermLabel' => $this->service->termLabel($fromTerm),
      'toTermLabel'   => $dto['toTerm'] ? $this->service->termLabel($dto['toTerm']) : null,
      'targetClass'   => $dto['targetClass'],
      'promoteKind'   => $kind,
    ]);
  }

  public function commit(CommitRequest $request, string $mode) {
    $this->validateMode($mode);

    $fromTerm = $this->activeTerm($request);
    $current  = $this->classroomFromPayload($request, $fromTerm);

    $this->service->commit(
      mode: $mode,
      fromTerm: $fromTerm,
      current: $current,
      payload: $request->payload,
      confirm: $request->confirm
    );

    return redirect()->route('admin.siswa.promosi.index', [
      'mode' => $mode,
      'classroom_id' => $current->id,
    ])->with(
      'success',
      $mode === 'promote'
        ? 'Siswa berhasil diproses untuk term tujuan.'
        : 'Siswa berhasil diluluskan dan data terkait telah dihapus.'
    );
  }

  private function validateMode(string $mode): void {
    abort_unless(in_array($mode, self::MODES, true), 404);
  }

  private function activeTerm(Request $request): AcademicTerm {
    $termId = HomeroomContext::activeTermId($request);
    $term = $termId ? AcademicTerm::find($termId) : null;

    abort_if(!$term, 500, 'Active term belum tersedia.');
    return $term;
  }

  private function sourceClasses(AcademicTerm $term, string $mode) {
    return Classroom::withoutActiveTerm()
      ->forTerm((int) $term->id)
      ->when($mode === 'graduate', fn ($q) => $q->where('tingkat', 12))
      ->ordered()
      ->get();
  }

  private function selectedClassroom(Request $request, AcademicTerm $term, $classes): Classroom {
    $classroomId = (int) $request->query('classroom_id', (int) ($classes->first()?->id ?? 0));
    $classroom = $classes->firstWhere('id', $classroomId) ?? $classes->first();

    abort_if(!$classroom, 404, 'Kelas sumber belum tersedia untuk term aktif.');
    abort_if((int) $classroom->term_id !== (int) $term->id, 403, 'Kelas sumber tidak sesuai term aktif.');

    return $classroom;
  }

  private function classroomFromInput(Request $request, AcademicTerm $term): Classroom {
    return $this->findClassroom((int) $request->input('classroom_id'), $term);
  }

  private function classroomFromPayload(Request $request, AcademicTerm $term): Classroom {
    return $this->findClassroom((int) data_get($request->payload, 'classroom_id'), $term);
  }

  private function findClassroom(int $classroomId, AcademicTerm $term): Classroom {
    $classroom = Classroom::withoutActiveTerm()->find($classroomId);

    abort_if(!$classroom, 404, 'Kelas sumber tidak ditemukan.');
    abort_if((int) $classroom->term_id !== (int) $term->id, 403, 'Kelas sumber tidak sesuai term aktif.');

    return $classroom;
  }
}
