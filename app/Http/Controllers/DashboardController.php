<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\{Siswa, Guru, Classroom, Attendance, AcademicTerm};

class DashboardController extends Controller {
  protected function auditScope(): string {
    $u = auth()->user();
    if ($u->hasRole('kesiswaan')) return 'kesiswaan';
    if ($u->hasRole('guru_bk'))   return 'guru-bk';
    return 'admin';
  }

  /**
   * Ambil term aktif (id).
   * Prefer request attribute (InjectActiveTerm), fallback DB.
  */
  private function activeTermId(Request $r): ?int {
    // 1) dari middleware InjectActiveTerm
    $id = (int) ($r->attributes->get('activeTermId') ?? 0);
    if ($id) return $id;
  
    // 2) fallback DB (safety)
    $id = AcademicTerm::where('is_active', 1)->value('id');
  
    return $id ? (int) $id : null;
  }

  /**
   * Helper: filter attendance agar hanya menghitung siswa yang benar-benar aktif
   * pada term (kelas-per-term) sesuai pivot term_classroom_siswa.
   *
   * ✅ member-safe: cocokkan siswa_id + classroom_id + term_id + status=active
   */
  private function memberSafeWhereExists($query, int $termId) {
    return $query->whereExists(function ($q) use ($termId) {
      $q->select(DB::raw(1))
        ->from('term_classroom_siswa as tcs')
        ->whereColumn('tcs.siswa_id', 'attendances.siswa_id')
        ->whereColumn('tcs.classroom_id', 'attendances.classroom_id')
        ->where('tcs.term_id', $termId)
        ->where('tcs.status', 'active');
    });
  }

  public function index(Request $r) {
    $user = $r->user();

    // 1) Arahkan wali_kelas ke dashboard khusus
    if ($user->hasRole('wali_kelas')) {
      return redirect()->route('wali.dashboard.index');
    }

    // 2) Batasi akses dashboard ini hanya untuk role terkait
    if (!$user->hasAnyRole(['admin', 'guru', 'kesiswaan', 'guru_piket', 'guru_bk', 'siswa'])) {
      abort(403, 'Anda tidak memiliki akses ke Dashboard ini.');
    }

    // === parsing tanggal (dipakai beneran) ===
    $dateParam = $r->query('date');
    try {
      $date = $dateParam ? Carbon::parse($dateParam) : Carbon::today();
    } catch (\Throwable $e) {
      $date = Carbon::today();
    }

    $title         = 'Dashboard';
    $formattedDate = $date->translatedFormat('d M Y');

    // ✅ term aktif (untuk semua agregasi)
    $termId = $this->activeTermId($r);
    
    if (!$termId) {
      if ($r->user()->hasRole('admin')) {
        return redirect()
          ->route('admin.terms.index')
          ->with('warning', 'Silakan set Tahun Ajaran / Semester aktif.');
      }
    
      return view('dashboard.empty', [
        'message' => 'Tahun Ajaran belum disetel. Silakan hubungi administrator.'
      ]);
    }

    // ===== Hitung jumlah siswa "aktif di term" via pivot =====
    $jumlahSiswa = (int) DB::table('term_classroom_siswa as tcs')
      ->where('tcs.term_id', $termId)
      ->where('tcs.status', 'active')
      ->distinct()
      ->count('tcs.siswa_id');

    $jumlahGuru = (int) Guru::count();

    // jumlah kelas pada term aktif
    $jumlahKelas = (int) Classroom::withoutActiveTerm()
      ->where('term_id', $termId)
      ->count();

    // status absen yang dihitung sebagai "absen" (izin/sakit/alpa)
    $absenStatuses = ['izin', 'sakit', 'alpa'];

    // ===== Persentase absen per tingkat (10/11/12) =====
    $tingkats = [10, 11, 12];
    $persenPerTingkat = [];

    foreach ($tingkats as $t) {
      // total siswa tingkat t pada term aktif (pivot)
      $totalSiswaT = (int) DB::table('term_classroom_siswa as tcs')
        ->join('classrooms as c', 'c.id', '=', 'tcs.classroom_id')
        ->where('tcs.term_id', $termId)
        ->where('tcs.status', 'active')
        ->where('c.term_id', $termId)
        ->where('c.tingkat', $t)
        ->distinct()
        ->count('tcs.siswa_id');

      // total absen (izin/sakit/alpa) tingkat t pada tanggal itu
      // ✅ gunakan distinct siswa_id agar tidak melenceng jika ada duplikat record
      $absensiT = (int) Attendance::query()
        ->join('classrooms as c', 'c.id', '=', 'attendances.classroom_id')
        ->where('attendances.term_id', $termId)
        ->whereDate('attendances.date', $date->toDateString())
        ->whereIn('attendances.status', $absenStatuses)
        ->where('c.term_id', $termId)
        ->where('c.tingkat', $t)
        ->distinct()
        ->count('attendances.siswa_id');

      $persenPerTingkat[$t] = $totalSiswaT ? round(($absensiT / $totalSiswaT) * 100, 1) : 0.0;
    }

    $persentaseAbsenKelasX   = $persenPerTingkat[10] ?? 0.0;
    $persentaseAbsenKelasXI  = $persenPerTingkat[11] ?? 0.0;
    $persentaseAbsenKelasXII = $persenPerTingkat[12] ?? 0.0;

    // ===== Persentase total absen sekolah =====
    // ✅ member-safe + distinct siswa
    $totalAbsensiSekolah = (int) $this->memberSafeWhereExists(
      Attendance::query()
        ->where('term_id', $termId)
        ->whereDate('date', $date->toDateString())
        ->whereIn('status', $absenStatuses),
      $termId
    )
      ->distinct()
      ->count('attendances.siswa_id');

    $persentaseTotalAbsen = $jumlahSiswa ? round(($totalAbsensiSekolah / $jumlahSiswa) * 100, 1) : 0.0;

    // ===== Cache rekap global (term + date) =====
    $cacheKeyGlobal = "dash:audit:global:v5:term={$termId}:date=" . $date->toDateString();

    [$rekapStatus, $kelasTopTerlambat, $recentActivities] = Cache::remember(
      $cacheKeyGlobal,
      now()->addSeconds(90),
      function () use ($termId, $date, $jumlahSiswa) {

        // ✅ byStatus juga member-safe + distinct siswa
        $byStatus = Attendance::select('status', DB::raw('COUNT(DISTINCT attendances.siswa_id) as jumlah'))
          ->where('term_id', $termId)
          ->whereDate('date', $date->toDateString())
          ->whereExists(function ($q) use ($termId) {
            $q->select(DB::raw(1))
              ->from('term_classroom_siswa as tcs')
              ->whereColumn('tcs.siswa_id', 'attendances.siswa_id')
              ->whereColumn('tcs.classroom_id', 'attendances.classroom_id')
              ->where('tcs.term_id', $termId)
              ->where('tcs.status', 'active');
          })
          ->groupBy('status')
          ->pluck('jumlah', 'status');

        // ✅ sudah tercatat = hanya siswa aktif di pivot (term-aware + member-safe)
        $sudah = (int) Attendance::query()
          ->where('term_id', $termId)
          ->whereDate('date', $date->toDateString())
          ->whereExists(function ($q) use ($termId) {
            $q->select(DB::raw(1))
              ->from('term_classroom_siswa as tcs')
              ->whereColumn('tcs.siswa_id', 'attendances.siswa_id')
              ->whereColumn('tcs.classroom_id', 'attendances.classroom_id')
              ->where('tcs.term_id', $termId)
              ->where('tcs.status', 'active');
          })
          ->distinct()
          ->count('attendances.siswa_id');

        $belum = max(0, $jumlahSiswa - $sudah);

        $rekap = collect([
          'hadir'     => (int) ($byStatus['hadir'] ?? 0),
          'terlambat' => (int) ($byStatus['terlambat'] ?? 0),
          'izin'      => (int) ($byStatus['izin'] ?? 0),
          'sakit'     => (int) ($byStatus['sakit'] ?? 0),
          'alpa'      => (int) ($byStatus['alpa'] ?? 0),
          'belum'     => (int) $belum,
        ]);

        // ✅ Top kelas terlambat: gunakan DISTINCT siswa_id (bukan COUNT(*))
        $kelasTopAgg = Attendance::query()
          ->select('classroom_id', DB::raw('COUNT(DISTINCT siswa_id) as terlambat_count'))
          ->where('term_id', $termId)
          ->whereDate('date', $date->toDateString())
          ->where('status', 'terlambat')
          ->whereExists(function ($q) use ($termId) {
            $q->select(DB::raw(1))
              ->from('term_classroom_siswa as tcs')
              ->whereColumn('tcs.siswa_id', 'attendances.siswa_id')
              ->whereColumn('tcs.classroom_id', 'attendances.classroom_id')
              ->where('tcs.term_id', $termId)
              ->where('tcs.status', 'active');
          })
          ->groupBy('classroom_id')
          ->orderByDesc('terlambat_count')
          ->limit(5)
          ->get();

        $classroomMap = Classroom::withoutActiveTerm()
          ->where('term_id', $termId)
          ->whereIn('id', $kelasTopAgg->pluck('classroom_id'))
          ->get(['id', 'nama_kelas', 'tingkat'])
          ->keyBy('id');

        $kelasTop = $kelasTopAgg->map(function ($row) use ($classroomMap) {
          $c = $classroomMap->get($row->classroom_id);
          return (object) [
            'id'             => (int) $row->classroom_id,
            'nama_kelas'     => $c->nama_kelas ?? '-',
            'tingkat'        => $c->tingkat ?? null,
            'terlambat_count'=> (int) $row->terlambat_count,
          ];
        });

        // Recent activities (tetap join siswa untuk tampilan)
        // ✅ optional: filter member-safe supaya tidak tampil data nyasar
        $recent = Attendance::with(['siswa:id,nama_lengkap,classroom_id', 'siswa.classroom:id,nama_kelas'])
          ->where('term_id', $termId)
          ->whereDate('date', $date->toDateString())
          ->whereExists(function ($q) use ($termId) {
            $q->select(DB::raw(1))
              ->from('term_classroom_siswa as tcs')
              ->whereColumn('tcs.siswa_id', 'attendances.siswa_id')
              ->whereColumn('tcs.classroom_id', 'attendances.classroom_id')
              ->where('tcs.term_id', $termId)
              ->where('tcs.status', 'active');
          })
          ->orderByDesc('time')
          ->limit(10)
          ->get(['id', 'siswa_id', 'status', 'time', 'date', 'classroom_id', 'term_id']);

        return [$rekap, $kelasTop, $recent];
      }
    );

    // === Arahkan ke dashboard siswa ===
    if ($user->hasRole('siswa')) {
      return view('dashboard.siswa', compact(
        'title', 'formattedDate', 'jumlahSiswa', 'jumlahGuru',
        'jumlahKelas', 'persentaseAbsenKelasX',
        'persentaseAbsenKelasXI', 'persentaseAbsenKelasXII',
        'persentaseTotalAbsen', 'rekapStatus', 'date'
      ));
    }

    $auditScope = $this->auditScope();
    $auditIndex = route('audit.attendance.index');

    return view('dashboard.index', compact(
      'title', 'formattedDate', 'jumlahSiswa', 'jumlahGuru',
      'jumlahKelas', 'persentaseAbsenKelasX', 'persentaseAbsenKelasXI',
      'persentaseAbsenKelasXII', 'persentaseTotalAbsen', 'rekapStatus',
      'kelasTopTerlambat', 'recentActivities', 'date', 'auditScope',
      'auditIndex'
    ));
  }
}