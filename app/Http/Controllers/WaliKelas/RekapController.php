<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

use App\Models\{Attendance, AcademicTerm, Siswa, HomeroomAssignment};

use App\Exports\MonthlyRecapExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Support\HomeroomContext;

class RekapController extends Controller {

  /**
   * Normalisasi periode + clamp ke rentang term.
   * range: '' (Bulanan) atau 'TERM' (Seluruh Term)
   * return [$month, $year, $range, Carbon $start, Carbon $end]
   */
  private function parsePeriodeWithTerm(Request $request, AcademicTerm $term): array {
    $today = Carbon::today();
    $month = (int) ($request->input('month', $today->month));
    $year  = (int) ($request->input('year',  $today->year));
    $range = (string) $request->input('range', ''); // '' | 'TERM'

    $termStart = Carbon::parse($term->start_date)->startOfDay();
    $termEnd   = Carbon::parse($term->end_date)->endOfDay();

    if ($range === 'TERM') {
      $start = $termStart->copy();
      $end   = $termEnd->copy();
    } else {
      $start = Carbon::create($year, max(1, min(12, $month)), 1)->startOfMonth();
      $end   = $start->copy()->endOfMonth();

      // Clamp ke rentang term
      if ($end < $termStart || $start > $termEnd) {
        $start = $termStart->copy();
        $end   = $termEnd->copy();
      } else {
        $start = $start->max($termStart);
        $end   = $end->min($termEnd);
      }
    }

    // Label UI mengikuti hasil clamp
    $month = (int) $start->month;
    $year  = (int) $start->year;

    return [$month, $year, $range, $start, $end];
  }

  /** Daftar nama bulan (ID) */
  private function bulanIndonesia(): array {
    return [
      1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
      7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
    ];
  }

  /**
   * Ambil siswa kelas "per term" via pivot resmi (status active), urut nama.
   * Versi refaktor: pakai HomeroomContext sebagai single source of truth.
   */
  private function siswaKelasPerTermUntukRekap(int $termId, int $classroomId) {
    // ambil semua status (atau beberapa status historis)
    $siswaIds = HomeroomContext::siswaIdsInClassTerm($termId, $classroomId, "*");
    
    if ($siswaIds->isEmpty()) return collect();
    
    return Siswa::query()
      ->whereIn('id', $siswaIds)
      ->orderBy('nama_lengkap')
      ->get(['id','nis','nama_lengkap','classroom_id']);
  }

  /**
   * Hitung rekap absensi S/I/A untuk siswa tertentu dalam rentang tanggal.
   * (Term-safe + Class-safe + Member-safe)
   */
  private function buildRekapAbsensi(int $termId, int $classroomId, $startDate, $endDate, array $siswaIds) {
    if (empty($siswaIds)) {
      return collect(); // tidak ada siswa terdaftar di term ini
    }

    return Attendance::query()
      ->where('term_id', $termId)
      ->where('classroom_id', $classroomId)
      ->whereBetween('date', [$startDate, $endDate])
      ->whereIn('siswa_id', $siswaIds) // ✅ cegah data nyasar
      ->get(['siswa_id','status','date'])
      ->groupBy('siswa_id')
      ->map(fn($items) => [
        'sakit' => $items->where('status','sakit')->count(),
        'izin'  => $items->where('status','izin')->count(),
        'alpa'  => $items->where('status','alpa')->count(),
      ]);
  }

  private function resolveHomeroom(Request $request): array {
    $termIdFromReq = (int) $request->input('term_id');

    // Pastikan user punya data guru
    $guru = auth()->user()->guru;
    abort_if(!$guru, 403, 'Akun Anda tidak terhubung dengan data guru.');

    if ($termIdFromReq) {
      // Mode laporan histori (term dipilih)
      $homeroom = HomeroomAssignment::query()
        ->where('guru_id', $guru->id)       // ✅ FIX: bukan user_id
        ->where('term_id', $termIdFromReq)
        ->whereNull('ended_at')            // opsional tapi biasanya benar untuk “aktif” di term tsb
        ->latest('started_at')             // ambil yang terbaru jika ada riwayat
        ->first();

      abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term tersebut.');
      return [$homeroom, $termIdFromReq];
    }

    // Mode default (term aktif)
    // ✅ FIX: jangan bergantung pada request attributes (middleware mungkin salah FK)
    $homeroom = $guru->currentHomeroom; // sudah pakai HomeroomContext::activeTermId()

    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');
    return [$homeroom, 0];
  }

  /** REKAP BULANAN (tampilan) */
  public function monthlyRecap(Request $request) {
    // Ambil term_id dari request (khusus laporan)
    [$homeroom, $termIdFromReq] = $this->resolveHomeroom($request);

    $classroom = $homeroom->classroom;
    abort_if(!$classroom, 422, 'Kelas binaan tidak ditemukan.');
    
    $termId = (int) ($termIdFromReq ?: ($homeroom->term_id ?? 0));
    abort_if(!$termId, 422, 'Term tidak valid.');
    
    $term = AcademicTerm::find($termId);

    if (!$term) {
      return redirect()
        ->route('dashboard')
        ->with('warning', 'Term tidak ditemukan. Hubungi admin.');
    }

    [$month, $year, $range, $startDate, $endDate] = $this->parsePeriodeWithTerm($request, $term);

    // ✅ Siswa resmi (pivot term)
    $siswa    = $this->siswaKelasPerTermUntukRekap($termId, (int) $classroom->id);
    $siswaIds = $siswa->pluck('id')->all();

    // Cache key (term + kelas + periode)
    $cacheKey = 'wali:rekap:absensi:v3'
      . ':term=' . $termId
      . ':class=' . (int) $classroom->id
      . ':range=' . ($range ?: 'MONTH')
      . ':start=' . $startDate->toDateString()
      . ':end=' . $endDate->toDateString();

    $rekapAbsensi = Cache::remember(
      $cacheKey,
      now()->addSeconds(120),
      function () use ($termId, $classroom, $startDate, $endDate, $siswaIds) {
        return $this->buildRekapAbsensi($termId, (int) $classroom->id, $startDate, $endDate, $siswaIds);
      }
    );

    $title          = 'Rekap Bulanan';
    $bulanIndonesia = $this->bulanIndonesia();

    return view('wali-kelas.rekap.rekapBulanan', compact(
      'title', 'classroom', 'siswa', 'rekapAbsensi', 'month',
      'year', 'range', 'bulanIndonesia', 'startDate',
      'endDate', 'term'
    ));
  }

  /** EXPORT REKAP BULANAN (Excel) */
  public function exportMonthlyRecap(Request $request) {
    // Ambil term_id dari request (khusus laporan)
    [$homeroom, $termIdFromReq] = $this->resolveHomeroom($request);

    $classroom = $homeroom->classroom;
    abort_if(!$classroom, 422, 'Kelas binaan tidak ditemukan.');

    $termId = (int) ($termIdFromReq ?: ($homeroom->term_id ?? 0));
    abort_if(!$termId, 422, 'Term tidak valid.');
    
    $term = AcademicTerm::find($termId);

    if (!$term) {
      return redirect()
        ->route('dashboard')
        ->with('warning', 'Term tidak ditemukan. Hubungi admin.');
    }

    [$month, $year, $range, $startDate, $endDate] = $this->parsePeriodeWithTerm($request, $term);

    // ✅ Siswa resmi (pivot term), urut nama
    $siswa = $this->siswaKelasPerTermUntukRekap($termId, (int) $classroom->id)
      ->map(function ($row) {
        // export hanya butuh 3 kolom
        return (object) [
          'id' => $row->id,
          'nis' => $row->nis,
          'nama_lengkap' => $row->nama_lengkap,
        ];
      });

    $siswaIds = $siswa->pluck('id')->all();

    $rekapAbsensi = $this->buildRekapAbsensi(
      $termId,
      (int) $classroom->id,
      $startDate,
      $endDate,
      $siswaIds
    );

    $periodeLabel = $range === 'TERM'
      ? ('TERM_' . ($term->code ?? $term->name ?? $term->id))
      : sprintf('%02d-%d', $month, $year);

    // SANITASI filename
    $safe = function (string $s): string {
      $s = preg_replace('/[\/\\\\:\*\?"<>\|]+/u', '-', $s);
      $s = preg_replace('/\s+/u', ' ', trim($s));
      return $s;
    };

    $kelas     = $safe((string) $classroom->nama_kelas);
    $termLabel = $safe((string) ($term->name ?? ($term->code ?? 'Term '.$term->id)));

    $filename = $range === 'TERM'
      ? "RekapAbsensi_{$kelas}_{$termLabel}.xlsx"
      : "RekapAbsensi_{$kelas}_{$termLabel}_{$periodeLabel}.xlsx";

    return Excel::download(
      new MonthlyRecapExport($rekapAbsensi, $siswa),
      $filename
    );
  }
}