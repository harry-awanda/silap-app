<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\{
  MorningActivity, MorningActivityAttendance,
  Siswa, HomeroomAssignment
};

use App\Support\HomeroomContext;

class MorningActivityAttendanceController extends Controller {
  /** Normalisasi & validasi tanggal (default: hari ini) */
  private function resolveDate(Request $request): string {
    $raw = $request->query('tanggal') ?? $request->input('tanggal') ?? Carbon::today()->toDateString();

    $v = Validator::make(
      ['tanggal' => $raw],
      ['tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today']],
      ['before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.']
    );

    return $v->fails() ? Carbon::today()->toDateString() : $raw;
  }
  
  /** GET /wali-kelas/kegiatan-absensi */
  public function index(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $title = 'Absensi Kegiatan Pagi';
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');
    
    $tanggal   = $this->resolveDate($request);
    $termId    = (int) $homeroom->term_id;
    $classroom = $homeroom->classroom;
    
    // filter kegiatan dari query
    $selectedActivityId = $request->query('activity_id');
    
    // master kegiatan
    $activities = MorningActivity::query()
      ->orderBy('nama')
      ->get();

    $headerActivityName = null;
    if (!empty($selectedActivityId)) {
      $headerActivityName = $activities->firstWhere('id', (int)$selectedActivityId)?->nama;
    }
    
    // query absensi
    $q = MorningActivityAttendance::query()
      ->with('siswa:id,nis,nama_lengkap') // pastikan relasi siswa() ada di model
      ->where('term_id', $termId)
      ->where('classroom_id', (int) $classroom->id)
      ->whereDate('tanggal', $tanggal);

    // kalau difilter per kegiatan master
    if (!empty($selectedActivityId)) {
      $q->where('morning_activity_id', (int) $selectedActivityId);
    }

    // rekap
    $hadir = (clone $q)->where('status', 'hadir')->count();
    $tidak = (clone $q)->where('status', 'tidak_hadir')->count();

    // daftar yang tidak hadir (untuk tabel "absents")
    $absents = (clone $q)
      ->where('status', 'tidak_hadir')
      ->orderBy('siswa_id')
      ->get();

    return view('wali-kelas.kegiatan.index', compact(
      'title', 'tanggal', 'classroom', 'activities',
      'selectedActivityId', 'headerActivityName',
      'hadir', 'tidak', 'absents'
    ));
  }


  /** GET /wali-kelas/kegiatan-absensi/create */
  public function create(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $title = 'Input Absensi Kegiatan Pagi';
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $tanggal   = $this->resolveDate($request);
    $termId    = (int) $homeroom->term_id;
    $classroom = $homeroom->classroom;

    // ✅ konsisten dengan index + blade: activity_id
    $selectedActivityId = $request->query('activity_id');

    // ✅ master kegiatan untuk dropdown
    $activities = MorningActivity::query()
      ->orderBy('nama')
      ->get();

    // kalau ada activity_id -> ambil recordnya untuk display
    $activity = $selectedActivityId
      ? $activities->firstWhere('id', (int) $selectedActivityId) // atau findOrFail
      : null;

    // ✅ siswa binaan TERM aktif
    $siswaIds = HomeroomContext::siswaIdsInClassTerm(
      $termId,
      (int) $classroom->id,
      'active'
    );

    $siswas = Siswa::query()
      ->whereIn('id', $siswaIds)
      ->orderBy('nama_lengkap')
      ->get(['id', 'nis', 'nama_lengkap']);

    // data absensi yang sudah ada (prefill)
    $existing = MorningActivityAttendance::query()
      ->where('term_id', $termId)
      ->where('classroom_id', (int) $classroom->id)
      ->whereDate('tanggal', $tanggal)
      ->get()
      ->keyBy('siswa_id');

    return view('wali-kelas.kegiatan.create', compact(
      'title',
      'tanggal',
      'classroom',
      'activities',           // ✅ tambah ini
      'selectedActivityId',   // ✅ tambah ini (untuk @selected)
      'activity',
      'siswas',
      'existing'
    ));
  }

  /** POST /wali-kelas/kegiatan-absensi */
  public function store(Request $request) {
    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom, 403, 'Anda bukan wali kelas pada term aktif.');

    $termId    = (int) $homeroom->term_id;
    $classroom = $homeroom->classroom;
    $tanggal   = $this->resolveDate($request);

    $validated = $request->validate([
      'morning_activity_id'  => ['nullable', 'exists:morning_activities,id'],
      'custom_activity_name' => ['nullable', 'string', 'max:100'],
      'status'               => ['required', 'array'],
      'status.*'             => ['required', Rule::in(['hadir', 'tidak_hadir'])],
      'keterangan'           => ['nullable', 'array'],
    ]);

    // pastikan salah satu jenis kegiatan ada
    abort_if(
      empty($validated['morning_activity_id']) && empty($validated['custom_activity_name']),
      422,
      'Pilih kegiatan atau isi nama kegiatan kustom.'
    );

    $activityId = $validated['morning_activity_id'] ?? null;
    $customName = $validated['custom_activity_name'] ?? null;

    DB::transaction(function () use (
      $validated, $tanggal, $termId,
      $classroom, $activityId, $customName
    ) {
      foreach ($validated['status'] as $sid => $status) {
        $siswaId = (int) $sid;

        // ✅ validasi siswa binaan TERM aktif
        HomeroomContext::assertSiswaBinaanTerm(
          $termId,
          (int) $classroom->id,
          $siswaId
        );

        MorningActivityAttendance::updateOrCreate(
          [
            'term_id'      => $termId,
            'tanggal'      => $tanggal,
            'siswa_id'     => $siswaId,
            'classroom_id' => (int) $classroom->id,
          ],
          [
            'morning_activity_id'  => $activityId,
            'custom_activity_name' => $customName,
            'status'               => $status,
            'keterangan'           => $validated['keterangan'][$sid] ?? null,
          ]
        );
      }
    });

    return redirect()
      ->route('kegiatan-absensi.index', ['tanggal' => $tanggal])
      ->with('success', 'Absensi kegiatan pagi berhasil disimpan.');
  }
}