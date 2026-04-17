<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\{
  AgendaPiket, GuruKbmAbsence, JadwalPiket,
  AcademicTerm, Guru, Siswa, Classroom,
  Attendance, Upload, ProfilSekolah
};

class AgendaPiketController extends Controller {
  /**
   * Term aktif (untuk create/store). Untuk export PDF pakai term_id dari agenda.
   */
  
  private function activeTerm(): ?AcademicTerm {
    return AcademicTerm::query()
      ->where('is_active', true)
      ->orderByDesc('id')
      ->first();
  }
  
  private function handleMissingActiveTerm(Request $request) {
    $user = $request->user();
    // Non-admin: tetap boleh akses halaman, tapi diberi pesan jelas
    return redirect()
      ->route('agenda_piket.index')
      ->with('warning', 'Term aktif belum diatur. Hubungi admin untuk menyetel Tahun Ajaran/Semester aktif.');
  }

  /**
   * Hitung jumlah siswa "kelas per term" (pivot) untuk 1 kelas pada 1 term.
   * Default: hanya status active.
   */
  private function siswaCountByClassTerm(int $termId, int $classroomId, string $status = 'active'): int {
    return DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('classroom_id', $classroomId)
      ->when($status !== '*', fn($q) => $q->where('status', $status))
      ->distinct()
      ->count('siswa_id');
  }

  /**
   * Ambil daftar kelas untuk term tertentu.
   */
  private function classesForTerm(int $termId) {
    return Classroom::query()
      ->where('term_id', $termId)
      ->orderBy('tingkat')
      ->orderBy('nama_kelas')
      ->get();
  }

  /**
   * Rekap absensi per kelas untuk tanggal tertentu (term-aware).
   * Return:
   * - $absensiPerKelas[classroom_id] = ['sakit'=>x,'izin'=>y,'alpa'=>z]
   * - $totalAbsensiPerTingkat[tingkat] = ['sakit'=>x,'izin'=>y,'alpa'=>z]
   * - $totalSiswaPerTingkat[tingkat] = n
   */
  private function buildRekapAbsensiPerKelasDanTingkat(int $termId, string $date): array {
    $kelas = $this->classesForTerm($termId);

    $absensiPerKelas = [];
    $totalSiswaPerTingkat = ['10' => 0, '11' => 0, '12' => 0];
    $totalAbsensiPerTingkat = [
      '10' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
      '11' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
      '12' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
    ];

    foreach ($kelas as $kelasItem) {
      $classroomId = (int) $kelasItem->id;
      $tingkat     = (string) $kelasItem->tingkat;

      // total siswa per kelas dari pivot (kelas per term)
      $cntSiswaKelas = $this->siswaCountByClassTerm($termId, $classroomId, 'active');

      // rekap sakit/izin/alpa dari attendance (term_id + classroom_id)
      $counts = Attendance::query()
        ->where('term_id', $termId)
        ->where('classroom_id', $classroomId)
        ->whereDate('date', $date)
        ->whereIn('status', ['sakit', 'izin', 'alpa'])
        ->select('status', DB::raw('COUNT(DISTINCT siswa_id) as jumlah'))
        ->groupBy('status')
        ->pluck('jumlah', 'status');

      $absensiPerKelas[$classroomId] = [
        'sakit' => (int) ($counts['sakit'] ?? 0),
        'izin'  => (int) ($counts['izin']  ?? 0),
        'alpa'  => (int) ($counts['alpa']  ?? 0),
      ];

      $totalSiswaPerTingkat[$tingkat] = ($totalSiswaPerTingkat[$tingkat] ?? 0) + $cntSiswaKelas;

      foreach (['sakit', 'izin', 'alpa'] as $s) {
        $totalAbsensiPerTingkat[$tingkat][$s] =
          ($totalAbsensiPerTingkat[$tingkat][$s] ?? 0) + $absensiPerKelas[$classroomId][$s];
      }
    }

    return [$kelas, $absensiPerKelas, $totalAbsensiPerTingkat, $totalSiswaPerTingkat];
  }

  /**
   * Tampilkan daftar agenda piket (role: guru_piket)
   */
  public function index() {
    $title = 'Agenda Harian Piket';

    // index tidak harus abort jika belum ada term aktif
    $activeTerm = AcademicTerm::query()
      ->where('is_active', true)
      ->orderByDesc('id')
      ->first();

    $agendaPikets = AgendaPiket::query()
      ->when($activeTerm, fn($q) => $q->where('term_id', $activeTerm->id))
      ->orderBy('tanggal', 'desc')
      ->get();

    return view('guru_piket.agenda_piket.index', compact('agendaPikets', 'title', 'activeTerm'));
  }

  /**
   * Formulir pembuatan agenda piket baru
   */
  public function create() {
    $title = 'Agenda Harian Piket';

    $hariMap = [
      'Monday'    => 'Senin',
      'Tuesday'   => 'Selasa',
      'Wednesday' => 'Rabu',
      'Thursday'  => 'Kamis',
      'Friday'    => 'Jumat',
      'Saturday'  => 'Sabtu',
      'Sunday'    => 'Minggu',
    ];

    $hariEn  = Carbon::now()->format('l');
    $hariIni = $hariMap[$hariEn] ?? $hariEn;

    $guruPiket = JadwalPiket::with('guru')
      ->where('hari_piket', $hariIni)
      ->get();

    $allGuru = Guru::orderBy('nama_lengkap')->get();

    return view('guru_piket.agenda_piket.create', compact('title', 'guruPiket', 'allGuru'));
  }

  /**
   * Simpan agenda piket baru
   */
  public function store(Request $request) {
    $rows = collect($request->input('absensi_guru', []))
      ->filter(fn($r) => filled($r['guru_id'] ?? null) && filled($r['status'] ?? null))
      ->map(fn($r) => [
        'guru_id'     => (int) $r['guru_id'],
        'status'      => $r['status'],
        'keterangan'  => $r['keterangan'] ?? null,
      ])
      ->values()
      ->all();

    $request->merge(['absensi_guru' => $rows]);

    $request->validate([
      'tanggal'           => 'required|date',
      'kejadian_normal'   => 'nullable|string',
      'kejadian_masalah'  => 'nullable|string',
      'solusi'            => 'nullable|string',
      'guru_piket'        => 'required|array',
      'guru_piket.*'      => 'exists:guru,id',
      'absensi_guru'              => 'sometimes|array',
      'absensi_guru.*.guru_id'    => 'required|exists:guru,id',
      'absensi_guru.*.status'     => 'required|in:sakit,izin,alpa',
      'absensi_guru.*.keterangan' => 'nullable|string|max:191',
    ]);

    $activeTerm = $this->activeTerm();
    if (!$activeTerm) {
      session()->flash('warning', 'Term aktif belum disetel. Hubungi admin untuk menyetel Tahun Ajaran/Semester aktif.');
    }

    DB::beginTransaction();
    try {
      $agendaPiket = AgendaPiket::create([
        'tanggal'          => $request->tanggal,
        'kejadian_normal'  => $request->kejadian_normal,
        'kejadian_masalah' => $request->kejadian_masalah,
        'solusi'           => $request->solusi,
        'guru_piket'       => json_encode($request->guru_piket),
        'term_id'          => $activeTerm->id,
      ]);

      foreach ($request->absensi_guru as $row) {
        GuruKbmAbsence::create([
          'agenda_piket_id' => $agendaPiket->id,
          'guru_id'         => $row['guru_id'],
          'status'          => $row['status'],
          'keterangan'      => $row['keterangan'] ?? null,
        ]);
      }

      // ✅ Rekap absensi siswa per kelas & tingkat (TERM-AWARE + KELAS PER TERM)
      [$kelas, $absensiPerKelas, $totalAbsensiPerTingkat, $totalSiswaPerTingkat] =
        $this->buildRekapAbsensiPerKelasDanTingkat((int) $activeTerm->id, (string) $request->tanggal);

      $agendaPiket->update([
        'absensi_per_kelas'   => json_encode($absensiPerKelas),
        'absensi_per_tingkat' => json_encode($totalAbsensiPerTingkat),
      ]);

      DB::commit();
      return redirect()->route('agenda_piket.index')->with('success', 'Agenda piket berhasil dibuat.');
    } catch (\Throwable $th) {
      DB::rollBack();
      return back()->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
    }
  }

  /**
   * Form edit agenda piket
   */
  public function edit($id) {
    $title = 'Edit Agenda Piket';

    $hariMap = [
      'Monday'    => 'Senin',
      'Tuesday'   => 'Selasa',
      'Wednesday' => 'Rabu',
      'Thursday'  => 'Kamis',
      'Friday'    => 'Jumat',
      'Saturday'  => 'Sabtu',
      'Sunday'    => 'Minggu',
    ];

    $hariEn  = Carbon::now()->format('l');
    $hariIni = $hariMap[$hariEn] ?? $hariEn;

    $agendaPiket = AgendaPiket::findOrFail($id);

    $guruPiket = JadwalPiket::with('guru')
      ->where('hari_piket', $hariIni)
      ->get();

    return view('guru_piket.agenda_piket.edit', compact('agendaPiket', 'title', 'guruPiket'));
  }

  /**
   * Update agenda piket
   */
  public function update(Request $request, $id) {
    $rows = collect($request->input('absensi_guru', []))
      ->filter(fn($r) => filled($r['guru_id'] ?? null) && filled($r['status'] ?? null))
      ->map(fn($r) => [
        'guru_id'     => (int) $r['guru_id'],
        'status'      => $r['status'],
        'keterangan'  => $r['keterangan'] ?? null,
      ])
      ->values()
      ->all();

    $request->merge(['absensi_guru' => $rows]);

    $request->validate([
      'tanggal'           => 'required|date',
      'kejadian_normal'   => 'nullable|string',
      'kejadian_masalah'  => 'nullable|string',
      'solusi'            => 'nullable|string',
      'guru_piket'        => 'required|array',
      'guru_piket.*'      => 'exists:guru,id',
      'absensi_guru'              => 'sometimes|array',
      'absensi_guru.*.guru_id'    => 'required|exists:guru,id',
      'absensi_guru.*.status'     => 'required|in:sakit,izin,alpa',
      'absensi_guru.*.keterangan' => 'nullable|string|max:191',
    ]);

    $agendaPiket = AgendaPiket::findOrFail($id);

    DB::beginTransaction();
    try {
      $agendaPiket->update([
        'tanggal'          => $request->tanggal,
        'kejadian_normal'  => $request->kejadian_normal,
        'kejadian_masalah' => $request->kejadian_masalah,
        'solusi'           => $request->solusi,
        'guru_piket'       => json_encode($request->guru_piket),
      ]);

      $agendaPiket->guruKbmAbsences()->delete();

      foreach ($request->absensi_guru as $row) {
        GuruKbmAbsence::create([
          'agenda_piket_id' => $agendaPiket->id,
          'guru_id'         => $row['guru_id'],
          'status'          => $row['status'],
          'keterangan'      => $row['keterangan'] ?? null,
        ]);
      }

      // ✅ Rebuild rekap absensi (TERM dari agenda)
      $termId = (int) $agendaPiket->term_id;

      [$kelas, $absensiPerKelas, $totalAbsensiPerTingkat, $totalSiswaPerTingkat] =
        $this->buildRekapAbsensiPerKelasDanTingkat($termId, (string) $request->tanggal);

      $agendaPiket->update([
        'absensi_per_kelas'   => json_encode($absensiPerKelas),
        'absensi_per_tingkat' => json_encode($totalAbsensiPerTingkat),
      ]);

      DB::commit();
      return redirect()->route('agenda_piket.index')->with('success', 'Agenda piket berhasil diperbarui.');
    } catch (\Throwable $th) {
      DB::rollBack();
      return back()->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
    }
  }

  /**
   * Hapus agenda piket
   */
  public function destroy($id) {
    $agendaPiket = AgendaPiket::findOrFail($id);
    $agendaPiket->delete();

    return redirect()->route('agenda_piket.index')->with('success', 'Agenda piket berhasil dihapus.');
  }

  /**
   * Export PDF
   */
  public function exportPdf($id) {
    $agendaPiket = AgendaPiket::findOrFail($id);

    $guruPiketIds = json_decode($agendaPiket->guru_piket, true) ?: [];
    $guruPiket = count($guruPiketIds)
      ? Guru::whereIn('id', $guruPiketIds)->pluck('nama_lengkap')->values()
      : collect();

    $absensiPerKelas = json_decode($agendaPiket->absensi_per_kelas, true) ?: [];

    $profilSekolah = ProfilSekolah::with(['kepalaSekolah', 'kesiswaan'])->first();

    // Kelas yang ada di absensiPerKelas saja (jika ada), tetap urut tingkat/nama
    $kelasQuery = Classroom::query();

    $agendaTermId = (int) $agendaPiket->term_id;
    $kelasQuery->where('term_id', $agendaTermId);

    if (!empty($absensiPerKelas)) {
      $kelasQuery->whereIn('id', array_map('intval', array_keys($absensiPerKelas)));
    }

    $kelas = $kelasQuery->orderBy('tingkat')->orderBy('nama_kelas')->get();

    $absensiGuru = $agendaPiket->guruKbmAbsences()
      ->with('guru:id,nama_lengkap')
      ->orderBy('status')->orderBy('guru_id')
      ->get();

    // Hitung total absensi per tingkat + total siswa per tingkat (TERM-AWARE)
    $totalSiswaPerTingkat = ['10' => 0, '11' => 0, '12' => 0];
    $totalAbsensiPerTingkat = [
      '10' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
      '11' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
      '12' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
    ];

    foreach ($kelas as $kelasItem) {
      $classroomId = (int) $kelasItem->id;
      $tingkat     = (string) $kelasItem->tingkat;

      // ✅ total siswa dari pivot term_classroom_siswa
      $totalSiswaPerTingkat[$tingkat] += $this->siswaCountByClassTerm($agendaTermId, $classroomId, 'active');

      if (isset($absensiPerKelas[$classroomId])) {
        foreach (['sakit', 'izin', 'alpa'] as $s) {
          $totalAbsensiPerTingkat[$tingkat][$s] += (int) ($absensiPerKelas[$classroomId][$s] ?? 0);
        }
      }
    }

    // Persentase per tingkat
    $persentase = [];
    foreach (['10', '11', '12'] as $tingkat) {
      $totalSiswa = max(1, (int) $totalSiswaPerTingkat[$tingkat]);
      $persentase[$tingkat] = [
        'sakit' => ($totalAbsensiPerTingkat[$tingkat]['sakit'] / $totalSiswa) * 100,
        'izin'  => ($totalAbsensiPerTingkat[$tingkat]['izin']  / $totalSiswa) * 100,
        'alpa'  => ($totalAbsensiPerTingkat[$tingkat]['alpa']  / $totalSiswa) * 100,
      ];
    }

    // Persentase total absen
    $totalSiswa = array_sum($totalSiswaPerTingkat);

    $totalAbsensi =
      ($totalAbsensiPerTingkat['10']['sakit'] + $totalAbsensiPerTingkat['11']['sakit'] + $totalAbsensiPerTingkat['12']['sakit'])
      + ($totalAbsensiPerTingkat['10']['izin'] + $totalAbsensiPerTingkat['11']['izin'] + $totalAbsensiPerTingkat['12']['izin'])
      + ($totalAbsensiPerTingkat['10']['alpa'] + $totalAbsensiPerTingkat['11']['alpa'] + $totalAbsensiPerTingkat['12']['alpa']);

    $persentaseTotalAbsen = $totalSiswa > 0 ? ($totalAbsensi / $totalSiswa) * 100 : 0;

    // Kop surat
    $kopSurat = Upload::where('description', 'like', '%kop surat%')->first();
    $imageSrc = null;

    if ($kopSurat && file_exists(storage_path('app/public/' . $kopSurat->file_path))) {
      $path = storage_path('app/public/' . $kopSurat->file_path);
      $imageData = base64_encode(file_get_contents($path));
      $imageType = mime_content_type($path);
      $imageSrc = "data:{$imageType};base64,{$imageData}";
    }

    Carbon::setLocale('id');
    $tanggal = Carbon::parse($agendaPiket->tanggal);

    $cssPath = public_path('assets/css/laporan-piket/styles.css');
    $css = file_exists($cssPath) ? file_get_contents($cssPath) : '';

    $pdf = Pdf::loadView('guru_piket.agenda_piket.pdf', compact(
      'agendaPiket', 'guruPiket', 'kelas', 'absensiGuru',
      'absensiPerKelas', 'totalAbsensiPerTingkat',
      'totalSiswaPerTingkat', 'persentase',
      'persentaseTotalAbsen', 'imageSrc', 'profilSekolah',
      'css', 'tanggal'
    ));

    $pdf->setPaper([0, 0, 595.276, 934.724], 'portrait');

    return $pdf->download('agenda_piket_' . $agendaPiket->tanggal . '.pdf');
  }
}