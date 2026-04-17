<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

use App\Models\{Classroom, Pelanggaran, PelanggaranSiswa};
use App\Support\{ActiveTerm, HomeroomContext};
use App\Services\PelanggaranSiswaService;
use App\Http\Requests\PelanggaranSiswa\{StoreRequest, UpdateRequest};
use App\Queries\PelanggaranSiswa\DatatableQuery;

class PelanggaranSiswaController extends Controller {
  public function __construct(private PelanggaranSiswaService $service) {}

  /* =========================
   * Helpers Role
   * ========================= */
  private function role(string $r): bool {
    return auth()->check() && auth()->user()->hasRole($r);
  }

  private function any(array $r): bool {
    return auth()->check() && auth()->user()->hasAnyRole($r);
  }

  private function homeroomCtx(Request $request): array {
    $termId = ActiveTerm::id($request);

    [$assignment, $classroomId, $guru] = HomeroomContext::forAuthed($request);

    $isHomeroom = (bool) $assignment;
    $cid = $classroomId ? (int) $classroomId : null;

    return [$termId, $isHomeroom, $cid];
  }

  /**
   * Gate untuk edit/hapus data.
   * - admin: read-only
   * - kesiswaan & guru_bk: boleh
   * - wali_kelas: hanya siswa binaan pada term aktif (cek pivot term_classroom_siswa)
   */
  private function canManageRecord(Request $request, PelanggaranSiswa $rec): bool {
    [$termId, $isHomeroom, $cid] = $this->homeroomCtx($request);

    if ($this->role('admin')) return false;

    if ($this->any(['kesiswaan', 'guru_bk'])) {
      // optional super ketat:
      // return (int)$rec->term_id === (int)$termId;
      return true;
    }

    if ($isHomeroom && $cid) {
      if ((int) $rec->term_id !== (int) $termId) return false;

      return HomeroomContext::siswaInClassTerm($termId, $cid, (int) $rec->siswa_id);
    }

    return false;
  }

  public function index(Request $request) {
    $title = 'Pelanggaran Siswa';

    $classrooms = collect();
    if ($this->any(['admin', 'kesiswaan', 'guru_bk'])) {
      $classrooms = Classroom::ordered()->get(['id', 'nama_kelas']);
    }

    return view('pelanggaranSiswa.index', compact('title', 'classrooms'));
  }

  /**
   * CREATE
   * - wali_kelas: siswa otomatis dari pivot term_classroom_siswa (kelas binaan)
   * - kesiswaan/guru_bk: pilih kelas dulu (GET classroom_id) => reload => siswa terisi
   */
  public function create(Request $request) {
    [$termId, $isHomeroom, $cid] = $this->homeroomCtx($request);

    // access
    abort_if($this->role('admin'), 403);
    abort_unless($this->any(['kesiswaan', 'guru_bk']) || $isHomeroom, 403);

    $title = 'Pelanggaran Siswa';

    $pelanggaranByJenis = Pelanggaran::orderBy('jenis')
      ->orderBy('nama')
      ->get()
      ->groupBy('jenis');

    $classrooms = collect();
    $siswa = collect();

    if ($this->any(['kesiswaan', 'guru_bk'])) {
      $classrooms = Classroom::ordered()->get(['id', 'nama_kelas']);

      // Opsi A: pilih kelas dulu via GET classroom_id => reload create
      $classroomId = (int) $request->query('classroom_id', 0);

      if ($classroomId > 0) {
        // Validasi sederhana: classroom harus ada di term aktif
        $validClass = Classroom::query()
          ->where('term_id', $termId)
          ->where('id', $classroomId)
          ->exists();

        if ($validClass) {
          $siswa = DB::table('term_classroom_siswa as tcs')
            ->join('siswa as s', 's.id', '=', 'tcs.siswa_id')
            ->where('tcs.term_id', $termId)
            ->where('tcs.classroom_id', $classroomId)
            ->where('tcs.status', 'active')
            ->orderBy('s.nama_lengkap')
            ->get(['s.id', 's.nama_lengkap', 's.nis']);
        }
      }
    } else {
      // wali kelas: batasi siswa via pivot term_classroom_siswa sesuai kelas binaan
      abort_unless($cid, 403);

      $siswa = DB::table('term_classroom_siswa as tcs')
        ->join('siswa as s', 's.id', '=', 'tcs.siswa_id')
        ->where('tcs.term_id', $termId)
        ->where('tcs.classroom_id', $cid)
        ->where('tcs.status', 'active')
        ->orderBy('s.nama_lengkap')
        ->get(['s.id', 's.nama_lengkap', 's.nis']);
    }

    // untuk prefill dropdown kelas (kesiswaan/guru_bk)
    $selectedClassroomId = (int) $request->query('classroom_id', 0);

    return view('pelanggaranSiswa.create', compact(
      'title', 'pelanggaranByJenis', 'classrooms',
      'siswa', 'isHomeroom', 'selectedClassroomId'
    ));
  }

  public function store(StoreRequest $request) {
    [$termId, $isHomeroom, $cid] = $this->homeroomCtx($request);

    abort_if($this->role('admin'), 403);
    abort_unless($this->any(['kesiswaan', 'guru_bk']) || $isHomeroom, 403);

    // Wali kelas: siswa harus binaan pada term aktif (pivot)
    if ($isHomeroom) {
      abort_unless($cid, 403);
      abort_unless(
        HomeroomContext::siswaInClassTerm($termId, $cid, (int) $request->siswa_id),
        403
      );
    }

    // Kesiswaan / Guru BK: jika form mengirim classroom_id, kunci siswa harus sesuai kelas tsb (pivot)
    $classroomId = (int) $request->input('classroom_id', 0);
    if ($this->any(['kesiswaan', 'guru_bk']) && $classroomId > 0) {
      abort_unless(
        HomeroomContext::siswaInClassTerm($termId, $classroomId, (int) $request->siswa_id),
        422
      );
    }

    $payload = $request->only(['siswa_id', 'tanggal_pelanggaran', 'keterangan']);

    // field by role
    if ($this->any(['kesiswaan', 'guru_bk']) || $isHomeroom) {
      $payload['status']   = $request->input('status');
      $payload['tindakan'] = $request->input('tindakan');
    }

    if ($isHomeroom) $payload['catatan_waliKelas'] = $request->input('catatan_waliKelas');
    if ($this->role('kesiswaan')) $payload['catatan_kesiswaan'] = $request->input('catatan_kesiswaan');
    if ($this->role('guru_bk'))   $payload['catatan_guruBK'] = $request->input('catatan_guruBK');

    $this->service->create($payload, (array) $request->pelanggaran, $termId);

    return redirect()
      ->route('pelanggaranSiswa.index')
      ->with('success', 'Pelanggaran berhasil ditambahkan.');
  }
  
  public function edit(Request $request, PelanggaranSiswa $pelanggaranSiswa) {
    abort_unless($this->canManageRecord($request, $pelanggaranSiswa), 403);
  
    $title = 'Edit Pelanggaran Siswa';
  
    $pelanggaranSiswa->load([
      'siswa:id,nama_lengkap,nis',
      'pelanggaran:id,nama,jenis',
    ]);
  
    $pelanggaranByJenis = Pelanggaran::orderBy('jenis')
      ->orderBy('nama')
      ->get()
      ->groupBy('jenis');
  
    $selectedIds = $pelanggaranSiswa->pelanggaran->pluck('id')->toArray();
  
    [$termId, $isHomeroom, $cid] = $this->homeroomCtx($request);
  
    $classrooms = collect();
    $siswa = collect();
  
    // Default classroom untuk edit:
    // - wali kelas: kelas binaan (cid)
    // - kesiswaan/guru_bk: ambil dari pivot term aktif berdasarkan siswa_id record, tapi boleh dioverride lewat query classroom_id
    $pivotClassroomId = (int) DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('siswa_id', $pelanggaranSiswa->siswa_id)
      ->value('classroom_id');
  
    if ($this->any(['kesiswaan', 'guru_bk'])) {
      $classrooms = Classroom::ordered()->get(['id', 'nama_kelas']);
  
      $selectedClassroomId = (int) $request->query('classroom_id', $pivotClassroomId);
  
      if ($selectedClassroomId > 0) {
        $validClass = Classroom::query()
          ->where('term_id', $termId)
          ->where('id', $selectedClassroomId)
          ->exists();
  
        if ($validClass) {
          $siswa = DB::table('term_classroom_siswa as tcs')
            ->join('siswa as s', 's.id', '=', 'tcs.siswa_id')
            ->where('tcs.term_id', $termId)
            ->where('tcs.classroom_id', $selectedClassroomId)
            ->where('tcs.status', 'active')
            ->orderBy('s.nama_lengkap')
            ->get(['s.id', 's.nama_lengkap', 's.nis']);
        }
      }
    } else {
      // Wali kelas: siswa hanya dari kelas binaan term aktif
      $selectedClassroomId = $cid ?: 0;
  
      if ($selectedClassroomId > 0) {
        $siswa = DB::table('term_classroom_siswa as tcs')
          ->join('siswa as s', 's.id', '=', 'tcs.siswa_id')
          ->where('tcs.term_id', $termId)
          ->where('tcs.classroom_id', $selectedClassroomId)
          ->where('tcs.status', 'active')
          ->orderBy('s.nama_lengkap')
          ->get(['s.id', 's.nama_lengkap', 's.nis']);
      }
    }
  
    return view('pelanggaranSiswa.edit', compact(
      'title', 'pelanggaranSiswa', 'pelanggaranByJenis',
      'selectedIds', 'classrooms', 'siswa', 'isHomeroom',
      'selectedClassroomId'
    ));
  }
  
  public function update(UpdateRequest $request, PelanggaranSiswa $pelanggaranSiswa) {
    [$termId, $isHomeroom, $cid] = $this->homeroomCtx($request);
    
    abort_if($this->role('admin'), 403);

    // =========================
    // VALIDASI TAMBAHAN (OPS A)
    // kesiswaan/guru_bk: jika mengirim classroom_id, siswa harus sesuai kelas tsb (pivot)
    // =========================
    $classroomId = (int) $request->input('classroom_id', 0);
    if ($this->any(['kesiswaan', 'guru_bk']) && $classroomId > 0) {
      abort_unless(
        HomeroomContext::siswaInClassTerm($termId, $classroomId, (int) $request->siswa_id),
        422,
        'Siswa tidak terdaftar pada kelas yang dipilih pada term aktif.'
      );
    }

    // =========================
    // AKSES & VALIDASI ROLE
    // =========================
    if (!$this->any(['kesiswaan', 'guru_bk'])) {
      // wali kelas
      abort_unless($isHomeroom && $cid, 403);

      // record harus term aktif
      abort_unless((int) $pelanggaranSiswa->term_id === (int) $termId, 403);

      // siswa yg dipilih harus binaan wali pada term aktif (pivot)
      abort_unless(
        HomeroomContext::siswaInClassTerm($termId, $cid, (int) $request->siswa_id),
        403
      );
    }
    
    $update = $request->only(['siswa_id', 'tanggal_pelanggaran', 'keterangan']);

    if ($this->any(['kesiswaan', 'guru_bk']) || $isHomeroom) {
      $update['status']   = $request->input('status');
      $update['tindakan'] = $request->input('tindakan');
    }

    if ($isHomeroom) $update['catatan_waliKelas'] = $request->input('catatan_waliKelas');
    if ($this->role('kesiswaan')) $update['catatan_kesiswaan'] = $request->input('catatan_kesiswaan');
    if ($this->role('guru_bk'))   $update['catatan_guruBK'] = $request->input('catatan_guruBK');

    $useTermId = (int) ($pelanggaranSiswa->term_id ?: $termId);

    $this->service->update(
      $pelanggaranSiswa,
      $update,
      (array) $request->pelanggaran,
      $useTermId
    );

    return redirect()
      ->route('pelanggaranSiswa.index')
    ->with('success', 'Pelanggaran berhasil diperbarui.');
  }

  public function destroy(Request $request, PelanggaranSiswa $pelanggaranSiswa) {
    abort_unless($this->canManageRecord($request, $pelanggaranSiswa), 403);

    $pelanggaranSiswa->pelanggaran()->detach();
    $pelanggaranSiswa->delete();

    return redirect()
      ->route('pelanggaranSiswa.index')
      ->with('success', 'Pelanggaran berhasil dihapus.');
  }

  public function show(Request $request, PelanggaranSiswa $pelanggaranSiswa) {
    [$termId, $isHomeroom, $cid] = $this->homeroomCtx($request);

    if ($this->role('admin') || $this->any(['kesiswaan', 'guru_bk'])) {
      // ok
    } elseif ($isHomeroom && $cid) {
      abort_unless((int) $pelanggaranSiswa->term_id === (int) $termId, 403);

      abort_unless(
        HomeroomContext::siswaInClassTerm($termId, $cid, (int) $pelanggaranSiswa->siswa_id),
        403
      );
    } else {
      abort(403);
    }

    $pelanggaranSiswa->load([
      'siswa:id,nama_lengkap,nis',
      'pelanggaran:id,nama,jenis',
    ]);

    // kelas menurut term aktif (pivot), bukan siswa.classroom_id
    $kelasNama = null;
    $kelasId = DB::table('term_classroom_siswa')
      ->where('term_id', $termId)
      ->where('siswa_id', $pelanggaranSiswa->siswa_id)
      ->value('classroom_id');

    if ($kelasId) {
      $kelasNama = Classroom::withoutActiveTerm()
        ->where('id', $kelasId)
        ->value('nama_kelas');
    }

    $statusText = $pelanggaranSiswa->status ? ucfirst($pelanggaranSiswa->status) : '—';
    $statusBadgeClass = $pelanggaranSiswa->status === 'selesai'
      ? 'bg-label-success'
      : ($pelanggaranSiswa->status ? 'bg-label-warning' : 'bg-label-secondary');

    $tindakanMap = [
      'pembinaan_wali_kelas'     => 'Pembinaan Wali Kelas',
      'pembinaan_guru_bk'        => 'Pembinaan Guru BK',
      'pembinaan_kepala_sekolah' => 'Pembinaan Kepala Sekolah',
    ];

    $tindakanText = $pelanggaranSiswa->tindakan
      ? ($tindakanMap[$pelanggaranSiswa->tindakan] ?? $pelanggaranSiswa->tindakan)
      : '—';

    return response()->json([
      'id' => $pelanggaranSiswa->id,
      'tanggal' => Carbon::parse($pelanggaranSiswa->tanggal_pelanggaran)->translatedFormat('d M Y'),
      'siswa' => [
        'nama'  => $pelanggaranSiswa->siswa->nama_lengkap ?? '—',
        'nis'   => $pelanggaranSiswa->siswa->nis ?? '—',
        'kelas' => $kelasNama ?? '—',
      ],
      'pelanggaran' => $pelanggaranSiswa->pelanggaran->map(fn($p) => [
        'nama'  => $p->nama,
        'jenis' => $p->jenis,
      ])->values(),
      'status' => [
        'text' => $statusText,
        'badge_class' => $statusBadgeClass,
      ],
      'tindakan'   => $tindakanText,
      'keterangan' => $pelanggaranSiswa->keterangan ?: '—',
      'catatan' => [
        'wali_kelas' => $pelanggaranSiswa->catatan_waliKelas ?: '—',
        'kesiswaan'  => $pelanggaranSiswa->catatan_kesiswaan ?: '—',
        'guru_bk'    => $pelanggaranSiswa->catatan_guruBK ?: '—',
      ],
      'updated_at' => optional($pelanggaranSiswa->updated_at)->format('d M Y H:i'),
      'created_at' => optional($pelanggaranSiswa->created_at)->format('d M Y H:i'),
    ]);
  }
  
  public function datatable(Request $request) {
    [$termId, $isHomeroom, $cid] = $this->homeroomCtx($request);

    // Filter kelas:
    // - wali kelas: paksa hanya kelas binaan
    // - admin/kesiswaan/guru_bk: boleh pilih classroom_id
    $classroomFilter = null;
    if ($isHomeroom) {
      $classroomFilter = $cid ?: -999999;
    } elseif ($this->any(['admin', 'kesiswaan', 'guru_bk']) && $request->filled('classroom_id')) {
      $classroomFilter = (int) $request->classroom_id;
    }

    $statusFilter = $request->filled('status') ? $request->status : null;

    $base = DatatableQuery::build($request, $termId, $classroomFilter, $statusFilter);

    return DataTables::of($base)
      ->addIndexColumn()

      // =========================
      // ACTIONS (Dropdown via partial blade)
      // =========================
      ->addColumn('actions', function ($row) {
        $id = (int) $row->id;

        // aturan sederhana:
        // - admin read-only (detail saja)
        // - lainnya: tampilkan detail + edit + delete
        $isAdmin = auth()->check() && auth()->user()->hasRole('admin');

        return view('pelanggaranSiswa.partials.actions', [
          'id'        => $id,
          'canEdit'   => !$isAdmin,
          'canDelete' => !$isAdmin,
        ])->render();
      })

      // =========================
      // Kolom tampilan
      // =========================
      ->editColumn('tanggal', fn($row) => Carbon::parse($row->tanggal_pelanggaran)->translatedFormat('d M Y'))
      ->addColumn('siswa_nama', fn($row) => $row->siswa_nama ?? '—')
      ->addColumn('kelas', fn($row) => $row->kelas_nama ?? '—')

      ->addColumn('status_badge', function ($row) {
        if (!$row->status) return '<span class="text-muted">—</span>';
        $cls = $row->status === 'selesai' ? 'bg-label-success' : 'bg-label-warning';
        return '<span class="badge ' . $cls . '">' . ucfirst($row->status) . '</span>';
      })

      ->addColumn('tindakan_text', function ($row) {
        if (!$row->tindakan) return '—';
        $map = [
          'pembinaan_wali_kelas'     => 'Pembinaan Wali Kelas',
          'pembinaan_guru_bk'        => 'Pembinaan Guru BK',
          'pembinaan_kepala_sekolah' => 'Pembinaan Kepala Sekolah',
        ];
        return $map[$row->tindakan] ?? $row->tindakan;
      })

      // =========================
      // RAW HTML columns
      // =========================
      ->rawColumns(['status_badge', 'actions'])

      // =========================
      // Search (custom, termasuk pelanggaran names via pivot)
      // =========================
      ->filter(function ($q) use ($request, $termId) {
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
          DatatableQuery::applySearch($q, $search, $termId);
        }
      }, true)
      
      ->toJson();
  }
}