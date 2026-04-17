<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\{
  Siswa, PelanggaranSiswa, HomeroomAssignment
};

use App\Support\HomeroomContext;

class PelanggaranSiswaController extends Controller {
  /** GET /wali-kelas/pelanggaran */
  public function index(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $termId    = (int) $homeroom->term_id;
    $classroom = $homeroom->classroom;
    $title     = 'Riwayat Pelanggaran Siswa';

    // siswa binaan term aktif
    $siswaIds = HomeroomContext::siswaIdsInClassTerm(
      $termId,
      (int) $classroom->id,
      '*'
    );

    $pelanggaran = PelanggaranSiswa::query()
      ->with(['siswa:id,nis,nama_lengkap'])
      ->where('term_id', $termId)
      ->where('classroom_id', (int) $classroom->id)
      ->when($siswaIds->isNotEmpty(), fn($q) => $q->whereIn('siswa_id', $siswaIds))
      ->orderByDesc('tanggal')
      ->paginate(15);

    return view('wali-kelas.pelanggaran.index', compact(
      'title',
      'pelanggaran',
      'classroom'
    ));
  }

  /** GET /wali-kelas/pelanggaran/create */
  public function create(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $termId    = (int) $homeroom->term_id;
    $classroom = $homeroom->classroom;
    $title     = 'Input Pelanggaran Siswa';

    // siswa binaan TERM aktif
    $siswaIds = HomeroomContext::siswaIdsInClassTerm(
      $termId,
      (int) $classroom->id,
      'active'
    );

    $siswas = Siswa::query()
      ->whereIn('id', $siswaIds)
      ->orderBy('nama_lengkap')
      ->get(['id', 'nis', 'nama_lengkap']);

    return view('wali-kelas.pelanggaran.create', compact(
      'title', 'classroom', 'siswas'
    ));
  }

  /** POST /wali-kelas/pelanggaran */
  public function store(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $termId    = (int) $homeroom->term_id;
    $classroom = $homeroom->classroom;

    $validated = $request->validate([
      'siswa_id'  => ['required', 'integer', 'exists:siswa,id'],
      'tanggal'   => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
      'jenis'     => ['required', 'string', 'max:100'],
      'keterangan'=> ['nullable', 'string'],
      'poin'      => ['required', 'integer', 'min:1'],
    ]);

    // ✅ validasi siswa binaan TERM aktif
    HomeroomContext::assertSiswaBinaanTerm(
      $termId,
      (int) $classroom->id,
      (int) $validated['siswa_id']
    );

    PelanggaranSiswa::create([
      'term_id'      => $termId,
      'classroom_id' => (int) $classroom->id,
      'siswa_id'     => (int) $validated['siswa_id'],
      'tanggal'      => $validated['tanggal'],
      'jenis'        => $validated['jenis'],
      'keterangan'   => $validated['keterangan'] ?? null,
      'poin'         => $validated['poin'],
    ]);

    return redirect()
      ->route('wali-kelas.pelanggaran.index')
      ->with('success', 'Data pelanggaran berhasil ditambahkan.');
  }

  /** DELETE /wali-kelas/pelanggaran/{pelanggaran} */
  public function destroy(Request $request, PelanggaranSiswa $pelanggaran) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $termId      = (int) $homeroom->term_id;
    $classroomId = (int) $homeroom->classroom_id;

    // kunci ke term & kelas
    abort_if(
      (int) $pelanggaran->term_id !== $termId ||
      (int) $pelanggaran->classroom_id !== $classroomId,
      403,
      'Tidak berwenang menghapus data ini.'
    );

    // safety tambahan (pivot term-aware)
    abort_unless(
      HomeroomContext::siswaInClassTerm(
        $termId,
        $classroomId,
        (int) $pelanggaran->siswa_id,
        '*'
      ),
      403,
      'Data siswa tidak sesuai dengan kelas binaan.'
    );

    $pelanggaran->delete();

    return back()->with('success', 'Data pelanggaran dihapus.');
  }
}