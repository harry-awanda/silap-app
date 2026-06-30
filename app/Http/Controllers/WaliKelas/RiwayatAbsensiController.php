<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\{Siswa, Attendance, HomeroomAssignment, Upload};
use App\Support\HomeroomContext;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RiwayatAbsensiController extends Controller {
  private array $filterableStatuses = ['izin', 'sakit', 'alpa', 'terlambat'];

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

  private function validateFilters(Request $request): void {
    if ($request->query('from') || $request->query('to') || $request->query('status')) {
      $request->validate([
        'from'   => ['nullable', 'date_format:Y-m-d'],
        'to'     => ['nullable', 'date_format:Y-m-d'],
        'status' => ['nullable', 'in:' . implode(',', $this->filterableStatuses)],
      ]);
    }
  }

  private function baseAttendanceQuery(int $termId, int $classroomId, int $siswaId, ?string $from, ?string $to) {
    return Attendance::query()
      ->where('term_id', $termId)
      ->where('classroom_id', $classroomId)
      ->where('siswa_id', $siswaId)
      ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
      ->when($to,   fn($q) => $q->whereDate('date', '<=', $to));
  }

  private function prepareContext(Request $request, Siswa $siswa): array {
    [$homeroom, $termId, $classroomId] = $this->homeroomContext($request);

    HomeroomContext::assertSiswaBinaanTerm($termId, $classroomId, (int) $siswa->id);

    $this->validateFilters($request);

    return [
      $homeroom,
      $termId,
      $classroomId,
      $request->query('from'),
      $request->query('to'),
      $request->query('status'),
    ];
  }

  public function index(Request $request, Siswa $siswa) {
    [$homeroom, $termId, $classroomId, $from, $to, $status] = $this->prepareContext($request, $siswa);

    $allItems = $this->baseAttendanceQuery($termId, $classroomId, (int) $siswa->id, $from, $to)->get();

    $items = $this->baseAttendanceQuery($termId, $classroomId, (int) $siswa->id, $from, $to)
      ->when($status, fn($q) => $q->where('status', $status))
      ->orderByDesc('date')
      ->orderByDesc('time')
      ->get();

    $rekap = [
      'hadir'     => $allItems->where('status', 'hadir')->count(),
      'izin'      => $allItems->where('status', 'izin')->count(),
      'sakit'     => $allItems->where('status', 'sakit')->count(),
      'alpa'      => $allItems->where('status', 'alpa')->count(),
      'terlambat' => $allItems->where('status', 'terlambat')->count(),
    ];

    $title = 'Riwayat Absensi Siswa';

    return view('wali-kelas.siswa.riwayat-absensi', compact(
      'title', 'siswa', 'items', 'rekap', 'from', 'to', 'status'
    ));
  }

  public function export(Request $request, Siswa $siswa) {
    $title = 'Riwayat Absensi Siswa';

    [$homeroom, $termId, $classroomId, $from, $to, $status] = $this->prepareContext($request, $siswa);

    $items = $this->baseAttendanceQuery($termId, $classroomId, (int) $siswa->id, $from, $to)
      ->when($status, fn($q) => $q->where('status', $status))
      ->orderByDesc('date')
      ->orderByDesc('time')
      ->get();

    $kopSurat = Upload::where('description', 'like', '%kop surat%')->first();
    $imageSrc = null;

    if ($kopSurat && $kopSurat->file_path && file_exists(storage_path('app/public/' . $kopSurat->file_path))) {
      $path      = storage_path('app/public/' . $kopSurat->file_path);
      $imageData = base64_encode(file_get_contents($path));
      $imageType = mime_content_type($path);
      $imageSrc  = "data:{$imageType};base64,{$imageData}";
    }

    $cssPath = public_path('assets/css/laporan-piket/styles.css');
    $css     = file_exists($cssPath) ? file_get_contents($cssPath) : '';

    Carbon::setLocale('id');

    $pdf = Pdf::loadView('wali-kelas.siswa.absensi.pdf', compact(
      'title', 'siswa', 'items', 'from', 'to', 'status', 'css', 'imageSrc'
    ))->setPaper('A4', 'portrait');

    $statusLabel = $status ? '_' . ucfirst($status) : '';

    return $pdf->download('Riwayat_Absensi' . $statusLabel . '_' . $siswa->nama_lengkap . '.pdf');
  }
}
