<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Classroom;
use App\Models\AcademicTerm;

class ClassroomController extends Controller {

  protected function activeTermId(): ?int {
    return AcademicTerm::where('is_active', true)->value('id');
  }

  protected function termLabel(AcademicTerm $t): string {
    $sem = ucfirst((string)$t->semester);
    return "{$t->year_start}/{$t->year_end} - {$sem}";
  }

  public function index() {
    $title = 'Data Kelas';
    $classrooms = Classroom::query()
      ->with(['currentHomeroom.guru'])
      ->select('classrooms.*')
      ->selectSub(function ($q) {
        $q->from('term_classroom_siswa as tcs')
          ->selectRaw('COUNT(*)')
          ->whereColumn('tcs.term_id', 'classrooms.term_id')
          ->whereColumn('tcs.classroom_id', 'classrooms.id')
          ->where('tcs.status', 'active');
      }, 'siswa_count')
        ->orderBy('tingkat')
        ->orderBy('nama_kelas')
        ->get();

    return view('admin.kelas.index', compact('classrooms','title'));
  }

  public function create() {
    $title = 'Data kelas';

    $terms = AcademicTerm::orderByDesc('year_start')
      ->orderByDesc('semester') // genap biasanya setelah ganjil
      ->limit(4)
      ->get();

    $activeTermId = $this->activeTermId();

    return view('admin.kelas.create', compact('title','terms','activeTermId'));
  }

  public function store(Request $request) {
    $activeTermId = $this->activeTermId();

    $validated = $request->validate([
      'term_id'    => ['nullable','integer','exists:academic_terms,id'],
      'nama_kelas' => ['required','string','max:100'],
      'tingkat'    => ['required','in:10,11,12'],
    ]);

    $termId = (int)($validated['term_id'] ?? $activeTermId);

    if (!$termId) {
      return back()->withInput()
        ->with('warning', 'Belum ada Tahun Ajaran aktif dan Anda belum memilih Term. Pilih Term terlebih dahulu.');
    }

    // Unique nama kelas per term
    $request->validate([
      'nama_kelas' => [
        Rule::unique('classrooms', 'nama_kelas')->where(fn($q)=>$q->where('term_id', $termId))
      ],
    ]);

    $ok = Classroom::create([
      'term_id'    => $termId,
      'nama_kelas' => $validated['nama_kelas'],
      'tingkat'    => $validated['tingkat'],
    ]);

    return $ok
      ? redirect()->route('admin.classrooms.index')->with('success', 'Berhasil menyimpan data!')
      : redirect()->route('admin.classrooms.index')->with('error', 'Gagal menyimpan data.');
  }

  public function edit(Classroom $classroom) {
    $title = 'Data kelas';

    $terms = AcademicTerm::query()
      ->orderByDesc('year_start')
      ->orderByDesc('semester')
      ->get();

    $activeTermId = $this->activeTermId();

    return view('admin.kelas.edit', compact('title','classroom','terms','activeTermId'));
  }
  
  public function update(Request $request, Classroom $classroom) {
    // term_id IMMUTABLE: tidak boleh diubah lewat edit
    $validated = $request->validate([
      'nama_kelas' => ['required','string','max:100'],
      'tingkat'    => ['required','in:10,11,12'],
    ]);
  
    // Unique nama kelas per term (term tetap: $classroom->term_id)
    $request->validate([
      'nama_kelas' => [
        Rule::unique('classrooms','nama_kelas')
          ->where(fn($q) => $q->where('term_id', $classroom->term_id))
          ->ignore($classroom->id),
      ],
    ]);
  
    $ok = $classroom->update([
      'nama_kelas' => $validated['nama_kelas'],
      'tingkat'    => $validated['tingkat'],
    ]);
  
    return $ok
      ? redirect()->route('admin.classrooms.index')->with('success', 'Berhasil memperbarui data!')
      : redirect()->route('admin.classrooms.index')->with('error', 'Gagal memperbarui data.');
  }

  public function destroy(Classroom $classroom) {
    return $classroom->delete()
      ? redirect()->route('admin.classrooms.index')->with('success', 'Berhasil menghapus data!')
      : redirect()->route('admin.classrooms.index')->with('error', 'Gagal menghapus data.');
  }

  /* =============================
   * OPSIONAL: CLONE STRUKTUR KELAS
   * ============================= */

  public function cloneForm() {
    $title = 'Clone Struktur Kelas';

    $terms = AcademicTerm::query()
      ->orderByDesc('year_start')
      ->orderByDesc('semester')
      ->get()
      ->map(function ($t) {
        $t->label = $this->termLabel($t);
        return $t;
      });

    $activeTermId = $this->activeTermId();

    return view('admin.kelas.clone', compact('title','terms','activeTermId'));
  }

  public function cloneCommit(Request $request) {
    $data = $request->validate([
      'from_term_id' => ['required','integer','exists:academic_terms,id'],
      'to_term_id'   => ['required','integer','exists:academic_terms,id','different:from_term_id'],
      'mode'         => ['nullable','in:skip,upsert'], // default skip
    ]);

    $mode = $data['mode'] ?? 'skip';

    $from = (int)$data['from_term_id'];
    $to   = (int)$data['to_term_id'];

    $classes = Classroom::query()
      ->where('term_id', $from)
      ->get(['nama_kelas','tingkat']);

    if ($classes->isEmpty()) {
      return back()->with('warning', 'Tidak ada kelas pada term sumber.');
    }

    $inserted = 0;
    $updated  = 0;

    DB::transaction(function () use ($classes, $to, $mode, &$inserted, &$updated) {
      foreach ($classes as $c) {
        $exists = Classroom::where('term_id', $to)
          ->where('nama_kelas', $c->nama_kelas)
          ->first();

        if ($exists) {
          if ($mode === 'upsert') {
            $exists->update(['tingkat' => $c->tingkat]);
            $updated++;
          }
          continue;
        }

        Classroom::create([
          'term_id'    => $to,
          'nama_kelas' => $c->nama_kelas,
          'tingkat'    => $c->tingkat,
        ]);
        $inserted++;
      }
    });

    return redirect()->route('admin.classrooms.index')
      ->with('success', "Clone selesai. Inserted: {$inserted}, Updated: {$updated}.");
  }
}
