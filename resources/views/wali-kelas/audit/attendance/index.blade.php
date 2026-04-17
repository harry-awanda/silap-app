{{-- resources/views/wali_kelas/audit/attendance/index.blade.php --}}
@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span>Laporan /</span>
  <span class="text-muted fw-light">Audit Presensi Kelas</span>
</h4>

<div class="card">
  <div class="card-header">
    {{-- Judul card --}}
    <h6 class="mb-0">
      <strong>{{ $classroom->nama_kelas ?? '—' }}</strong> •
      Tanggal: <span id="labelTanggal">{{ $date ?? $today }}</span>
    </h6>

    <div class="row g-2 mt-2">
      <div class="col-sm-4 col-md-3">
        <label class="form-label mb-1">Tanggal</label>
        <input type="date" id="filterDate" class="form-control" value="{{ $date ?? $today }}">
      </div>
      <div class="col-sm-4 col-md-3">
        <label class="form-label mb-1">Status</label>
        <select id="filterStatus" class="form-select">
          <option value="">Semua</option>
          @php $ops = ['hadir'=>'Hadir','terlambat'=>'Terlambat','izin'=>'Izin','sakit'=>'Sakit','alpa'=>'Alpa']; @endphp
          @foreach($ops as $k=>$v)
            <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
          @endforeach
          <option value="belum" @selected(($status ?? '')==='belum')>Belum Presensi</option>
        </select>
      </div>
    </div>

    <div class="mt-3 d-flex gap-2">
      <button id="btnReset" class="btn btn-light">Reset</button>
      <button id="btnApply" class="btn btn-primary">Terapkan</button>
      <a id="btnExport" class="btn btn-outline-secondary" href="#">
        <i class="bx bx-export me-1"></i>Export
      </a>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="auditTable" class="table table-sm table-striped table-hover align-middle w-100">
        <thead>
          <tr>
            <th style="width:36px;"></th>
            <th>Waktu</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>Status</th>
            {{-- Kolom IP dihapus --}}
            <th style="width:200px;">Aksi</th>
          </tr>
        </thead>
        <tbody><!-- client-side fill --></tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
  const $date      = document.getElementById('filterDate');
  const $status    = document.getElementById('filterStatus');
  const $labelTgl  = document.getElementById('labelTanggal');
  const $btnApply  = document.getElementById('btnApply');
  const $btnReset  = document.getElementById('btnReset');
  const $btnExport = document.getElementById('btnExport');

  const overrideUrlPattern = @json(route('wali.audit.attendance.mark-present', '__ID__'));

  function buildDataUrl() {
    const p = new URLSearchParams();
    if ($date.value)   p.set('date', $date.value);
    if ($status.value) p.set('status', $status.value);
    return "{{ route('wali.audit.attendance.data') }}?" + p.toString();
  }

  function buildExportUrl() {
    const p = new URLSearchParams();
    if ($date.value)   p.set('date', $date.value);
    if ($status.value) p.set('status', $status.value);
    return "{{ route('wali.audit.attendance.export') }}?" + p.toString();
  }

  function getAttendanceId(row) {
    const keys = ['attendance_id','absen_id','att_id','attendanceId','id_absen'];
    for (const k of keys) if (row?.[k] != null && row[k] !== '') return row[k];
    return null;
  }
  function getClassroomId(row) {
    const keys = ['classroom_id','kelas_id','class_id','classroomId'];
    for (const k of keys) if (row?.[k] != null && row[k] !== '') return row[k];
    return null;
  }
  function getStudentId(row) {
    // Cari id siswa di beberapa kemungkinan key
    const keys = ['siswa_id','student_id','id_siswa','sid','studentId'];
    for (const k of keys) {
      if (row?.[k] != null && row[k] !== '') return row[k];
    }
    return null;
  }
  function normalizeStatus(row) {
    const candidates = [
      row?.status, row?.status_text, row?.status_raw, row?.statusLabel,
      row?.status_label, row?.statusHtml, row?.status_html, row?.status_badge
    ].filter(Boolean).map(String);

    for (const v of candidates) {
      const plain = v.replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim().toLowerCase();
      if (!plain) continue;
      if (plain.includes('terlambat') || plain.includes('late')) return 'terlambat';
      if (plain.includes('hadir') || plain.includes('present'))  return 'hadir';
      if (plain.includes('izin'))                                return 'izin';
      if (plain.includes('sakit'))                               return 'sakit';
      if (plain.includes('alpa') || plain.includes('absent'))    return 'alpa';
      if (plain.includes('belum'))                               return 'belum';
    }
    return '';
  }

  function formatDetails(detail_json) {
    let meta = {};
    try { meta = JSON.parse(detail_json || '{}'); } catch(e) {}
    const acc = (meta.accuracy_m ?? '-') ;
    const lat = (meta.latitude   ?? '-') ;
    const lng = (meta.longitude  ?? '-') ;
    const src = meta.source      ?? '-' ;
    const ua  = meta.user_agent  ?? '-' ;
    const dt  = meta.date        ?? '-' ;
    const tm  = meta.time
      ? new Date('1970-01-01T'+meta.time+'Z').toLocaleTimeString('id-ID', {hour12:false})
      : '-';

    return `
      <div class="p-2 border rounded bg-light">
        <div class="row g-2">
          <div class="col-md-3"><strong>Tanggal/Waktu</strong><br>${dt} ${tm}</div>
          <div class="col-md-3"><strong>Sumber</strong><br>${src}</div>
          <div class="col-md-3"><strong>Koordinat</strong><br>Lat: ${lat}, Lng: ${lng}</div>
          <div class="col-md-3"><strong>Akurasi (m)</strong><br>${acc}</div>
          <div class="col-md-12"><strong>User-Agent</strong><br><code class="small">${ua}</code></div>
        </div>
      </div>
    `;
  }
  
  function renderMarkPresentBtn(row) {
    const attId       = getAttendanceId(row);
    const siswaId     = getStudentId(row);
    const statusPlain = normalizeStatus(row); // 'terlambat', 'alpa', 'belum', dst.

    // 1) Kalau status "terlambat" dan ada attendance_id -> override by ID (PATCH)
    if (attId && statusPlain === 'terlambat') {
      const url = overrideUrlPattern.replace('__ID__', attId);
      return `
        <button type="button"
          data-mode="attendance"
          data-url="${url}"
          class="btn btn-sm btn-success btn-mark-present"
          title="Ubah TERLAMBAT → HADIR">
          Tandai Hadir
        </button>
      `;
    }

    // 2) Kalau status "belum" (belum presensi) -> override by siswa_id + date (POST)
    if (!attId && siswaId && statusPlain === 'belum') {
      const dateValue = document.getElementById('filterDate')?.value || '{{ $today }}';

      return `
        <button type="button"
          data-mode="student"
          data-siswa-id="${siswaId}"
          data-date="${dateValue}"
          class="btn btn-sm btn-success btn-mark-present"
          title="Tandai HADIR (walau belum presensi)">
          Tandai Hadir
        </button>
      `;
    }

    return '<span class="text-muted">—</span>';
  }

  let openRowIdx = null;
  let $openBtn   = null;
  function setBtnState($btn, expanded) {
    if (!$btn) return;
    $btn.text(expanded ? 'Tutup' : 'Detail')
       .attr('aria-expanded', expanded ? 'true' : 'false');
  }
  function closeOpenRow() {
    if (openRowIdx === null) return;
    const r = table.row(openRowIdx);
    if (r && r.child && r.child.isShown()) {
      const trOpen = $(r.node());
      r.child.hide();
      trOpen.removeClass('shown');
    }
    setBtnState($openBtn, false);
    openRowIdx = null;
    $openBtn   = null;
  }

  const table = $('#auditTable').DataTable({
    processing: false,
    serverSide: false,
    searching: true,
    lengthChange: true,
    pageLength: 25,
    order: [[1, 'asc']], // kolom 'Waktu'
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-nowrap text-end',
        render: function(){ return `<button type="button" class="btn btn-sm btn-outline-primary btn-detail" aria-expanded="false">Detail</button>`; }
      },
      { data: 'time' },
      { data: 'nis'  },
      { data: 'nama' },
      { data: 'status_badge' }, // HTML badge dari server
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-nowrap',
        render: function(_d,_t,row){ return renderMarkPresentBtn(row) || '<span class="text-muted">—</span>'; }
      },
    ],
    columnDefs: [
      { targets: [4], render: function(data){ return data; } } // jangan escape badge
    ]
  });

  table.on('draw', function () {
    openRowIdx = null;
    $openBtn   = null;
    $('#auditTable .btn-detail[aria-expanded="true"]').each(function(){
      setBtnState($(this), false);
    });
  });

  async function loadData() {
    const url = buildDataUrl();
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const json = await res.json();
    const rows = (Array.isArray(json) ? json : (json?.data || [])).map(it => {
      if (getClassroomId(it) == null) it.classroom_id = {{ $classroom->id ?? 'null' }};
      const s = normalizeStatus(it);
      if (!it.status && s) it.status = s;
      return it;
    });

    table.clear().rows.add(rows).draw();
  }

  $('#auditTable tbody').on('click', '.btn-detail', function () {
    const $btn = $(this);
    const tr   = $btn.closest('tr');
    const row  = table.row(tr);

    if (row.index() === openRowIdx && row.child.isShown()) {
      closeOpenRow();
      return;
    }

    closeOpenRow();
    const d = row.data();
    row.child(formatDetails(d.detail_json)).show();
    tr.addClass('shown');
    setBtnState($btn, true);
    openRowIdx = row.index();
    $openBtn   = $btn;
  });
  
  $('#auditTable tbody').on('click', '.btn-mark-present', async function () {
    const $btn  = $(this);
    const mode  = $btn.data('mode'); // 'attendance' atau 'student'
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    let fetchOptions = {
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      }
    };
    let url = '';
    
    if (mode === 'attendance') {
      url = $btn.data('url');
      fetchOptions.method = 'PATCH';
      fetchOptions.body   = null;
    } else if (mode === 'student') {
      url = @json(route('wali.audit.attendance.mark-present-by-student'));
      fetchOptions.method = 'POST';
      fetchOptions.body   = JSON.stringify({
        siswa_id: $btn.data('siswa-id'),
        date:     $btn.data('date'),
      });
    } else {
      return;
    }
    
    try {
      $btn.prop('disabled', true);
      const res  = await fetch(url, fetchOptions);
      const data = await res.json().catch(() => ({}));
      
      if (res.ok && data.ok) {
        closeOpenRow?.();
        await loadData();
      } else {
        alert(data?.message || 'Gagal mengubah status.');
        $btn.prop('disabled', false);
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan jaringan.');
      $btn.prop('disabled', false);
    }
  });

  // Events filter
  $btnApply.addEventListener('click', () => {
    $labelTgl.textContent = $date.value || '{{ $today }}';
    loadData();
    $btnExport.setAttribute('href', buildExportUrl());
  });
  $btnReset.addEventListener('click', () => {
    $date.value   = '{{ $today }}';
    $status.value = '';
    $labelTgl.textContent = '{{ $today }}';
    loadData();
    $btnExport.setAttribute('href', buildExportUrl());
  });

  // Init awal
  $btnExport.setAttribute('href', buildExportUrl());
  loadData();
})();
</script>
@endpush
