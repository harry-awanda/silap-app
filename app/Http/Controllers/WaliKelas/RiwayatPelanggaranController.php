<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Siswa, PelanggaranSiswa, Upload, HomeroomAssignment};
use App\Support\HomeroomContext;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RiwayatPelanggaranController extends Controller {
  /**
   * Ambil homeroom dari request + termId/classroomId
   */
  private function homeroomContext(Request $request): array {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');

    abort_if(!$homeroom || !$homeroom->classroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $termId      = (int) $homeroom->term_id;
    $classroomId = (int) $homeroom->classroom->id;

    return [$homeroom, $termId, $classroomId];
  }

  /**
   * Validasi optional range tanggal (from/to)
   */
  private function validateRange(Request $request): void {
    if ($request->query('from') || $request->query('to')) {
      $request->validate([
        'from' => ['nullable', 'date_format:Y-m-d'],
        'to'   => ['nullable', 'date_format:Y-m-d'],
      ]);
    }
  }

  public function index(Request $request, Siswa $siswa) {
    [$homeroom, $termId, $classroomId] = $this->homeroomContext($request);

    // ✅ otorisasi term-aware (kelas per term) — single source of truth
    HomeroomContext::assertSiswaBinaanTerm($termId, $classroomId, (int) $siswa->id);

    $this->validateRange($request);

    $from = $request->query('from');
    $to   = $request->query('to');

    $items = PelanggaranSiswa::with('dataPelanggaran')
      ->where('term_id', $termId)                 // ✅ kunci term
      ->where('siswa_id', (int) $siswa->id)       // ✅ kunci siswa
      ->when($from, fn($q) => $q->whereDate('tanggal_pelanggaran', '>=', $from))
      ->when($to,   fn($q) => $q->whereDate('tanggal_pelanggaran', '<=', $to))
      ->orderByDesc('tanggal_pelanggaran')
      ->paginate(10);

    $title = 'Riwayat Pelanggaran Siswa';

    return view('wali-kelas.siswa.pelanggaran.index', compact(
      'title', 'siswa', 'items', 'from', 'to'
    ));
  }

  public function more(Request $request, Siswa $siswa) {
    [$homeroom, $termId, $classroomId] = $this->homeroomContext($request);

    HomeroomContext::assertSiswaBinaanTerm($termId, $classroomId, (int) $siswa->id);

    $this->validateRange($request);

    $from = $request->query('from');
    $to   = $request->query('to');

    $items = PelanggaranSiswa::with('dataPelanggaran')
      ->where('term_id', $termId)
      ->where('siswa_id', (int) $siswa->id)
      ->when($from, fn($q) => $q->whereDate('tanggal_pelanggaran', '>=', $from))
      ->when($to,   fn($q) => $q->whereDate('tanggal_pelanggaran', '<=', $to))
      ->orderByDesc('tanggal_pelanggaran')
      ->paginate(10);

    return view('wali-kelas.siswa.pelanggaran._items', compact('items'))->render();
  }

  public function export(Request $request, Siswa $siswa) {
    $title = 'Riwayat Pelanggaran Siswa';

    [$homeroom, $termId, $classroomId] = $this->homeroomContext($request);

    HomeroomContext::assertSiswaBinaanTerm($termId, $classroomId, (int) $siswa->id);

    $this->validateRange($request);

    $from = $request->query('from');
    $to   = $request->query('to');

    $items = PelanggaranSiswa::with('dataPelanggaran')
      ->where('term_id', $termId)
      ->where('siswa_id', (int) $siswa->id)
      ->when($from, fn($q) => $q->whereDate('tanggal_pelanggaran', '>=', $from))
      ->when($to,   fn($q) => $q->whereDate('tanggal_pelanggaran', '<=', $to))
      ->orderByDesc('tanggal_pelanggaran')
      ->get();

    // ====== Kop Surat ======
    $kopSurat = Upload::where('description', 'like', '%kop surat%')->first();
    $imageSrc = null;

    if ($kopSurat && $kopSurat->file_path && file_exists(storage_path('app/public/' . $kopSurat->file_path))) {
      $path      = storage_path('app/public/' . $kopSurat->file_path);
      $imageData = base64_encode(file_get_contents($path));
      $imageType = mime_content_type($path);
      $imageSrc  = "data:{$imageType};base64,{$imageData}";
    }

    // ====== CSS ======
    $cssPath = public_path('assets/css/laporan-piket/styles.css');
    $css     = file_exists($cssPath) ? file_get_contents($cssPath) : '';

    Carbon::setLocale('id');

    $pdf = Pdf::loadView('wali-kelas.siswa.pelanggaran.pdf', compact(
      'title', 'siswa', 'items', 'from', 'to', 'css', 'imageSrc'
    ))->setPaper('A4', 'portrait');

    return $pdf->download('Riwayat_Pelanggaran_' . $siswa->nama_lengkap . '.pdf');
  }
}