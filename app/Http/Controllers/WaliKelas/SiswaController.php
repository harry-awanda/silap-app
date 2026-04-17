<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Http\Requests\WaliKelas\Siswa\UpdateSiswaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\{Siswa, HomeroomAssignment};
use App\Support\HomeroomContext;

class SiswaController extends Controller {
  /* =========================================
   * INDEX
   * ========================================= */

  public function index(Request $request) {
    $title = 'Siswa Binaan';

    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom || !$homeroom->classroom, 403, 'Anda belum memiliki kelas binaan.');

    $classroom   = $homeroom->classroom;
    $classroomId = (int) $classroom->id;

    // ✅ term aktif konsisten (InjectActiveTerm / session / cache / db)
    $termId = HomeroomContext::activeTermId($request);

    // ✅ Ambil siswa berdasarkan pivot term_classroom_siswa (status active)
    $siswaIds = HomeroomContext::siswaIdsInClassTerm($termId, $classroomId, 'active');

    $siswa = $siswaIds->isEmpty()
      ? collect()
      : Siswa::query()
        ->whereIn('id', $siswaIds)
        ->orderBy('nama_lengkap')
        ->get(['id', 'nis', 'nama_lengkap', 'jenis_kelamin', 'photo']);

    /**
     * Preferensi tampilan (list / grid)
     * default: list
     */
    $view = $request->query('view', 'list');

    // jika view butuh info term, gunakan term_id saja (atau load AcademicTerm di view bila perlu)
    return view('wali-kelas.siswa.index', compact(
      'title', 'siswa', 'classroom', 'termId', 'view'
    ));
  }

  /* =========================================
   * SHOW
   * ========================================= */

  public function show(Request $request, Siswa $siswa) {
    $this->assertBinaan($request, (int) $siswa->id);

    // optional: load classroom shortcut (boleh null / tidak akurat lintas term)
    $siswa->load('classroom');

    $kontak = [
      'siswa' => $siswa->kontak,
      'ayah'  => $siswa->kontak_ayah,
      'ibu'   => $siswa->kontak_ibu,
      'wali'  => $siswa->kontak_wali,
    ];

    $pelanggaran = $siswa->pelanggaranSiswa()->latest()->limit(20)->get();
    $timeline = $pelanggaran->map(function ($p) {
      $status = strtolower($p->tingkat ?? $p->kategori ?? '');
      $ikon = match ($status) {
        'berat'  => 'danger',
        'sedang' => 'warning',
        'ringan' => 'info',
        default  => 'primary'
      };

      return [
        'judul'      => $p->judul ?? ($p->jenis_pelanggaran ?? 'Pelanggaran'),
        'status'     => $status ?: null,
        'ikon'       => $ikon,
        'tanggal'    => $p->tanggal_pelanggaran ?? $p->created_at,
        'waktu'      => null,
        'ringkas'    => $p->keterangan ?? null,
        'detail_url' => route('siswa.pelanggaran.index', $p->siswa_id),
      ];
    });

    $title = 'Detail Siswa';

    return view('wali-kelas.siswa.show', compact(
      'title', 'siswa', 'kontak', 'timeline'
    ));
  }

  /* =========================================
   * EDIT
   * ========================================= */

  public function edit(Request $request, Siswa $siswa) {
    $this->assertBinaan($request, (int) $siswa->id);

    $title = 'Edit Data Siswa';
    return view('wali-kelas.siswa.edit', compact('title', 'siswa'));
  }

  /* =========================================
   * UPDATE
   * ========================================= */

  public function update(UpdateSiswaRequest $request, Siswa $siswa) {
    $this->assertBinaan($request, (int) $siswa->id);

    $validated = $request->validated();

    DB::transaction(function () use ($request, $siswa, $validated) {
      $data = $validated;

      // handle foto (crop/base64 diprioritaskan)
      $newPhotoPath = $this->storePhotoIfAny($request, $siswa);

      if ($newPhotoPath) {
        $data['photo'] = $newPhotoPath;
      } else {
        unset($data['photo']); // jangan overwrite jadi null
      }

      unset($data['photo_cropped']); // tidak masuk DB

      $siswa->update($data);
    });

    return redirect()->back()->with('success', 'Data siswa berhasil diperbarui.');
  }

  private function storePhotoIfAny(Request $request, Siswa $siswa): ?string {
    $dir = 'uploads/siswa';

    // === PRIORITAS: hasil crop (base64) ===
    if ($request->filled('photo_cropped')) {
      $base64 = $request->input('photo_cropped');

      if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $m)) {
        abort(422, 'Format data gambar tidak valid.');
      }

      $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
      $binary = base64_decode(substr($base64, strpos($base64, ',') + 1));
      if ($binary === false) {
        abort(422, 'Gagal memproses gambar hasil crop.');
      }

      $newPath = $dir . '/' . date('Ymd_His') . '_' . Str::random(8) . '.' . $ext;
      Storage::disk('public')->put($newPath, $binary);

      if ($siswa->photo && Storage::disk('public')->exists($siswa->photo)) {
        Storage::disk('public')->delete($siswa->photo);
      }

      return $newPath;
    }

    // === fallback: upload file biasa ===
    if ($request->hasFile('photo')) {
      $newPath = $request->file('photo')->store($dir, 'public');

      if ($siswa->photo && Storage::disk('public')->exists($siswa->photo)) {
        Storage::disk('public')->delete($siswa->photo);
      }

      return $newPath;
    }

    return null;
  }

  /* =========================================
   * DESTROY
   * ========================================= */

  public function destroy(Request $request, Siswa $siswa) {
    $this->assertBinaan($request, (int) $siswa->id);

    DB::transaction(function () use ($siswa) {
      $siswa->delete();
    });

    return back()->with('success', 'Siswa berhasil dihapus.');
  }

  /* =========================================
   * Helpers konteks (term aktif + kelas binaan)
   * ========================================= */

  private function homeroomClassroomId(Request $request): int {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom || !$homeroom->classroom, 403, 'Anda belum memiliki kelas binaan.');
    return (int) $homeroom->classroom->id;
  }

  /**
   * Pastikan siswa adalah binaan wali (berdasarkan pivot term aktif)
   */
  private function assertBinaan(Request $request, int $siswaId): void {
    $termId      = HomeroomContext::activeTermId($request);
    $classroomId = $this->homeroomClassroomId($request);

    HomeroomContext::assertSiswaBinaanTerm($termId, $classroomId, $siswaId);
  }
}