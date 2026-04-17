<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

use App\Models\HomeroomAssignment;
use App\Models\Classroom;
use App\Models\Guru;
use App\Models\AcademicTerm;

class HomeroomAssignmentController extends Controller {

  public function index() {
    $title = 'Homeroom Assignments';
    $termId = $this->activeTermId();
    
    if (!$termId) {
      return $this->redirectNoActiveTerm();
    }

    $assignments = HomeroomAssignment::with(['classroom','guru'])
      ->forTerm($termId)
      ->orderByRaw('ended_at IS NULL DESC')
      ->orderByDesc('started_at')
      ->get();

    return view('admin.homeroom.index', compact('title','assignments'));
  }

  public function create() {
    $title = 'Tambah Penugasan Wali Kelas';
    $termId = $this->activeTermId();
    
    if (!$termId) {
      return $this->redirectNoActiveTerm();
    }

    $classrooms = Classroom::where('term_id', $termId)
      ->orderBy('tingkat')->orderBy('nama_kelas')->get();

    $gurus = Guru::orderBy('nama_lengkap')->get();

    return view('admin.homeroom.create', compact('title','classrooms','gurus'));
  }

  public function store(Request $request) {
    $termId = $this->activeTermId();
    
    if (!$termId) {
      return $this->redirectNoActiveTerm();
    }

    $data = $request->validate([
      'classroom_id' => ['required','integer','exists:classrooms,id'],
      'guru_id'      => ['required','integer','exists:guru,id'],
      'started_at'   => ['nullable','date'],
    ]);

    // Guard: pastikan classroom yang dipilih memang di term aktif
    $classTerm = Classroom::where('id', $data['classroom_id'])->value('term_id');
    if ((int)$classTerm !== (int)$termId) {
      return back()->withErrors(['classroom_id' => 'Kelas tidak berada pada Term aktif.'])->withInput();
    }

    HomeroomAssignment::assignSafely(
      $termId,
      (int)$data['classroom_id'],
      (int)$data['guru_id'],
      isset($data['started_at']) ? new \DateTime($data['started_at']) : null
    );

    return redirect()->route('admin.homeroom.index')->with('success', 'Penugasan wali kelas diaktifkan.');
  }

  public function edit(HomeroomAssignment $homeroom) {
    $title  = 'Edit Penugasan Wali Kelas';
    $termId = $this->activeTermId();

    if (!$termId) {
      return $this->redirectNoActiveTerm();
    }

    // Optional guard: hanya boleh edit data dalam term aktif
    if ((int)$homeroom->term_id !== (int)$termId) {
      abort(403, 'Penugasan ini bukan pada Term aktif.');
    }

    $classrooms = Classroom::where('term_id', $termId)
      ->orderBy('tingkat')->orderBy('nama_kelas')->get();

    $gurus = Guru::orderBy('nama_lengkap')->get();

    return view('admin.homeroom.edit', compact('title','homeroom','classrooms','gurus'));
  }
  
  public function update(Request $request, HomeroomAssignment $homeroom) {
    $termId = $this->activeTermId();

    if (!$termId) {
      return $this->redirectNoActiveTerm();
    }
        
    if ((int)$homeroom->term_id !== (int)$termId) {
      abort(403, 'Penugasan ini bukan pada Term aktif.');
    }
    
    $data = $request->validate([
      'classroom_id' => ['required','integer','exists:classrooms,id'],
      'guru_id'      => ['required','integer','exists:guru,id'],
      'started_at'   => ['nullable','date'],
      // ended_at sengaja tidak diedit dari sini (tetap lewat tombol "Akhiri")
    ]);
    
    // Guard: classroom harus berada di term aktif
    $classTerm = Classroom::where('id', $data['classroom_id'])->value('term_id');
    if ((int)$classTerm !== (int)$termId) {
      return back()->withErrors(['classroom_id' => 'Kelas tidak berada pada Term aktif.'])->withInput();
    }
    
    DB::transaction(function () use ($homeroom, $termId, $data) {
      
      // Cegah konflik: jika record ini AKTIF, pastikan tidak ada penugasan aktif lain di kelas tsb pada term ini
      if (is_null($homeroom->ended_at)) {
        $existsOtherActive = HomeroomAssignment::where('term_id', $termId)
        ->where('classroom_id', (int)$data['classroom_id'])
        ->whereNull('ended_at')
        ->where('id', '!=', $homeroom->id)
        ->exists();
        
        if ($existsOtherActive) {
          abort(422, 'Kelas tersebut sudah memiliki wali kelas aktif pada Term ini.');
        }
      }
      
      $homeroom->update([
        'classroom_id' => (int)$data['classroom_id'],
        'guru_id'      => (int)$data['guru_id'],
        'started_at'   => isset($data['started_at']) ? new \DateTime($data['started_at']) : null,
      ]);
    });
    
    return redirect()->route('admin.homeroom.index')->with('success', 'Penugasan wali kelas berhasil diperbarui.');
  }

  public function destroy(HomeroomAssignment $homeroom) {
    // Hapus riwayat (aktif/non-aktif). Aman karena constraint dijaga oleh baris lain
    $homeroom->delete();
    return back()->with('success', 'Riwayat penugasan dihapus.');
  }

  public function end(Request $request, HomeroomAssignment $homeroom) {
    // Akhiri penugasan ini
    if ($homeroom->ended_at) {
      return back()->with('info', 'Penugasan ini sudah berakhir.');
    }
    $homeroom->endNow();

    return back()->with('success', 'Penugasan wali kelas diakhiri.');
  }

  // Ganti sesuai mekanisme Anda mendapatkan term aktif
  protected function activeTermId() {
    $id = AcademicTerm::where('is_active', 1)->value('id');
    return $id ? (int) $id : null;
  }

  protected function redirectNoActiveTerm() {
    return redirect()
      ->route('admin.terms.index') // sesuaikan route menu Periode Akademik
      ->with('warning', 'Tidak ada Term aktif. Tetapkan dulu pada menu Periode Akademik.');
  }
}