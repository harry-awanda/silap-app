@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">Laporan Presensi</span>
</h4>

<div class="card mb-4">
  <!-- <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <h5 class="mb-0">Audit Presensi</h5>
    <small class="text-muted">
      Tanggal: {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }}
    </small>
  </div> -->

  <div class="card-body">
    {{-- ==================== FILTER BAR ==================== --}}
    <form method="GET" action="{{ route('audit.attendance.index') }}" class="row g-2 align-items-end mb-3">
      <div class="col-sm-4 col-md-3">
        <label class="form-label" for="date">Tanggal</label>
        <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
      </div>

      <div class="col-sm-4 col-md-3">
        <label class="form-label" for="status">Status</label>
        @php
          $statuses = [
            ''          => 'Semua',
            'hadir'     => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin'      => 'Izin',
            'sakit'     => 'Sakit',
            'alpa'      => 'Alpa',
            'belum'     => 'Belum Presensi',
          ];
        @endphp
        <select class="form-select" id="status" name="status">
          @foreach($statuses as $val => $label)
            <option value="{{ $val }}" @selected((string)$status === (string)$val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      @if(isset($classrooms) && count($classrooms))
      <div class="col-sm-4 col-md-3">
        <label class="form-label" for="classroom_id">Kelas</label>
        <select class="form-select select2" id="classroom_id" name="classroom_id" data-placeholder="Semua">
          <option value="">Semua</option>
          @foreach($classrooms as $c)
            <option value="{{ $c->id }}" @selected((string)request('classroom_id')===(string)$c->id)>
              {{ $c->nama_kelas }}
            </option>
          @endforeach
        </select>
      </div>
      @endif

      <div class="col-sm-12 mt-3 d-flex flex-wrap gap-2">
        <a href="{{ route('audit.attendance.index') }}" class="btn btn-outline-secondary"> {{-- ✅ update --}}
          Reset
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="bx bx-filter-alt me-1"></i> Terapkan
        </button>

        {{-- Export mengikuti filter aktif --}}
        <a id="btnExport" href="{{ route('audit.attendance.export', request()->query()) }}" class="btn btn-success">
          <i class="bx bx-download me-1"></i> Export
        </a>
      </div>
    </form>

    @role('admin')
      @php
        $purgeOptions = [30, 60, 90];
      @endphp
      <div class="alert alert-warning d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between mb-3" role="alert">
        <div>
          <div class="fw-semibold">Hapus history presensi hadir</div>
          <div class="small">
            Menghapus data <strong>hadir</strong> yang lebih lama dari pilihan hari. Data 30/60/90 hari terakhir tetap disimpan.
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          @foreach($purgeOptions as $days)
            @php
              $cutoff = now()->startOfDay()->subDays($days)->toDateString();
            @endphp
            <form
              method="POST"
              action="{{ route('audit.attendance.purge-present-history', array_merge(request()->query(), ['days' => $days])) }}"
              onsubmit="return confirm('Hapus semua data presensi HADIR sebelum {{ $cutoff }}? Data {{ $days }} hari terakhir tidak akan dihapus.');"
            >
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bx bx-trash me-1"></i> &gt; {{ $days }} hari
              </button>
            </form>
          @endforeach
        </div>
      </div>
    @endrole

    {{-- ==================== RINGKASAN STATUS ==================== --}}
    @php
      $rekap = collect($rekapStatus ?? []);
      $num   = fn($k) => (int) ($rekap[$k] ?? 0);
      $linkBase = fn($s) => route('audit.attendance.index', array_filter([
        'date'=>$date, 'status'=>$s, 'classroom_id'=>request('classroom_id')
      ], fn($v)=>$v!==null && $v!==''));
      
      $cards = [
        ['key'=>'hadir',     'label'=>'Hadir',          'bs'=>'success',  'icon'=>'bx-check-circle'],
        ['key'=>'belum',     'label'=>'Belum Presensi', 'bs'=>'secondary','icon'=>'bx-minus-circle'],
        ['key'=>'terlambat', 'label'=>'Terlambat',      'bs'=>'warning',  'icon'=>'bx-time'],
        ['key'=>'sakit',     'label'=>'Sakit',          'bs'=>'primary',  'icon'=>'bx-first-aid'],
        ['key'=>'izin',      'label'=>'Izin',           'bs'=>'info',     'icon'=>'bx-edit'],
        ['key'=>'alpa',      'label'=>'Alpa',           'bs'=>'danger',   'icon'=>'bx-x-circle'],
      ];
    @endphp

    <div class="row g-3 align-items-stretch mb-3">
      @foreach ($cards as $i => $c)
        @php
          $count = $num($c['key']);
          $href = $count > 0 ? $linkBase($c['key']) : '#';
          $disabled = $count === 0 ? 'disabled pe-none' : '';
        @endphp
        <div class="col-6 col-md-4">
          <div class="card h-100 shadow-sm card-stat card-border-shadow-{{ $c['bs'] }}">
            <div class="card-body d-flex align-items-center gap-2 position-relative">
              <div class="pe-2">
                <h4 class="mb-1">{{ $count }}</h4>
                <small class="text-muted">{{ $c['label'] }}</small>
              </div>
              <span class="badge bg-label-{{ $c['bs'] }} rounded p-2 ms-auto stat-icon">
                <i class="bx {{ $c['icon'] }}"></i>
              </span>
              <a href="{{ $href }}" class="stretched-link {{ $disabled }}" aria-disabled="{{ $count === 0 ? 'true' : 'false' }}"></a>
            </div>
          </div>
        </div>

        @if (($i + 1) % 3 === 0)
          <div class="w-100 d-none d-md-block"></div>
        @endif
      @endforeach
    </div>

    {{-- ==================== TABS ==================== --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-rekap" role="tab">Rekap per Kelas</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-detail" role="tab">Detail Aktivitas</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-late" role="tab">Top Siswa Terlambat</button></li>
    </ul>

    <div class="tab-content">
      {{-- ========== TAB: REKAP PER KELAS ========== --}}
      <div class="tab-pane fade show active" id="tab-rekap" role="tabpanel">
        <div class="table-responsive">
          <table id="tbl-rekap" class="table table-hover align-middle datatable w-100">
            <thead>
              <tr>
                <th style="width:60px;">#</th>
                <th>Kelas</th>
                <th class="text-end">Hadir</th>
                <th class="text-end">Belum Presensi</th>
                <th class="text-end">Terlambat</th>
                <th class="text-end">Izin</th>
                <th class="text-end">Sakit</th>
                <th class="text-end">Alpa</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($kelasRekap as $k)
                @php
                  $hadir      = $k->hadir_count ?? 0;
                  $terlambat  = $k->terlambat_count ?? 0;
                  $izin       = $k->izin_count ?? 0;
                  $sakit      = $k->sakit_count ?? 0;
                  $alpa       = $k->alpa_count ?? 0;
                  $students   = $k->students_count ?? 0;
                  $totalToday = $k->total_today_count ?? 0;
                  $belum      = max(0, $students - $totalToday);
                  $total      = $hadir + $terlambat + $izin + $sakit + $alpa;
                @endphp
                <tr>
                  <td></td>
                  <td>
                    <a href="{{ route('audit.attendance.index', array_filter(['date'=>$date,'classroom_id'=>$k->id,'status'=>request('status')], fn($v)=>$v!==null && $v!=='')) }}">
                      {{ $k->nama_kelas ?? ('Kelas #'.$k->id) }}
                    </a>
                  </td>
                  <td class="text-end"><span class="badge bg-label-success">{{ $hadir }}</span></td>
                  <td class="text-end"><span class="badge bg-label-secondary">{{ $belum }}</span></td>
                  <td class="text-end"><span class="badge bg-label-warning">{{ $terlambat }}</span></td>
                  <td class="text-end"><span class="badge bg-label-info">{{ $izin }}</span></td>
                  <td class="text-end"><span class="badge bg-label-primary">{{ $sakit }}</span></td>
                  <td class="text-end"><span class="badge bg-label-danger">{{ $alpa }}</span></td>
                  <td class="text-end fw-semibold">{{ $total }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- ========== TAB: DETAIL AKTIVITAS ========== --}}
      <div class="tab-pane fade" id="tab-detail" role="tabpanel">
        <div class="table-responsive">
          <table id="dt-detail" class="table table-sm table-striped table-hover align-middle w-100">
            <thead>
              <tr>
                <th style="width:40px;"></th>
                <th style="width:90px;">Waktu</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th style="width:120px;">Status</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

      {{-- ========== TAB: TOP SISWA TERLAMBAT ========== --}}
      <div class="tab-pane fade" id="tab-late" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <small class="text-muted">
            Periode: awal bulan s/d {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}.
          </small>
        </div>
        <div class="table-responsive">
          <table id="dt-late" class="table table-sm table-striped table-hover align-middle w-100">
            <thead>
              <tr>
                <th style="width:60px;">#</th>
                <th>Nama Siswa</th>
                <th>Kelas (Saat Ini)</th>
                <th class="text-center" style="width:100px;">Total</th>
                <th style="width:140px;">Terakhir</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<style>
  #dt-detail td.dt-control { cursor: pointer; }
</style>
<script>
(function(){
  if (window.$ && $.fn.select2) {
    $('.select2').select2({ width:'100%', allowClear:true, placeholder:'Pilih' });
  }

  const form = document.querySelector('form[action="{{ route('audit.attendance.index') }}"]');
  const btnExport = document.getElementById('btnExport');

  function buildExportUrl() {
    if (!form || !btnExport) return;
    const params = new URLSearchParams(new FormData(form));
    btnExport.href = "{{ route('audit.attendance.export') }}?" + params.toString();
  }
  form?.querySelectorAll('input,select').forEach(el => el.addEventListener('change', buildExportUrl));
  buildExportUrl();

  const dtRekap = $('#tbl-rekap').DataTable({
    paging: true, searching: true, lengthChange: true, pageLength: 10,
    order: [[1,'asc']], columnDefs: [
      {targets:0,searchable:false,orderable:false},
      {targets:[2,3,4,5,6,7,8],className:'text-end'}
    ],
    responsive: true,
    language: {emptyTable:'Data rekap kosong.', zeroRecords:'Tidak ada hasil untuk filter ini.'}
  });
  dtRekap.on('order.dt search.dt draw.dt',function(){
    let i=dtRekap.page.info().start+1;
    dtRekap.column(0,{search:'applied',order:'applied',page:'current'}).nodes()
      .each(cell=>{cell.innerHTML=i++;});
  }).draw();

  const $date=document.getElementById('date');
  const $status=document.getElementById('status');
  const $kelas=document.getElementById('classroom_id');
  const fmtDetail=row=>{
    const dash=v=>(v===null||v===undefined||v==='')?'-':v;
    return `<div class="p-2">
      <div class="row g-2">
        <div class="col-md-3"><strong>Latitude</strong><br>${dash(row.latitude)}</div>
        <div class="col-md-3"><strong>Longitude</strong><br>${dash(row.longitude)}</div>
        <div class="col-md-3"><strong>Accuracy (m)</strong><br>${dash(row.accuracy_m)}</div>
        <div class="col-md-3"><strong>Source</strong><br>${dash(row.source)}</div>
      </div>
      <div class="mt-2"><strong>User-Agent</strong><br><small class="text-muted">${dash(row.user_agent)}</small></div>
    </div>`;
  };

  const table=$('#dt-detail').DataTable({
    processing:true,serverSide:true,searching:true,lengthChange:true,pageLength:25,order:[[1,'desc']],
    ajax:{
      url:"{{ route('audit.attendance.dt') }}",
      data:d=>{
        d.date=$date?.value||'{{ $date }}';
        d.status=$status?.value||'{{ $status }}';
        d.classroom_id=$kelas?.value||'{{ $kelasId }}';
      }
    },
    columns:[
      {data:null,orderable:false,searchable:false,className:'dt-control text-center',defaultContent:'<i class="bx bx-chevron-down"></i>'},
      {data:'waktu',name:'attendances.time',searchable:false},
      {data:'nama',name:'s.nama_lengkap'},
      {data:'kelas',name:'c.nama_kelas'},
      {data:'status_badge',name:'attendances.status'}
    ],
    createdRow:(row,data)=>{$('td',row).eq(4).html(data.status_badge);}
  });
  $('#dt-detail tbody').on('click','td.dt-control',function(){
    const tr=$(this).closest('tr');const row=table.row(tr);
    if(row.child.isShown()){row.child.hide();tr.removeClass('shown');$(this).html('<i class="bx bx-chevron-down"></i>');}
    else{row.child(fmtDetail(row.data())).show();tr.addClass('shown');$(this).html('<i class="bx bx-chevron-up"></i>');}
  });
  [$date,$status,$kelas].forEach(el=>el?.addEventListener('change',()=>table.ajax.reload()));
  buildExportUrl();

  const tblLate=$('#dt-late').DataTable({
    processing:true,serverSide:true,searching:true,lengthChange:true,pageLength:25,
    order:[[3,'desc'],[1,'asc']],
    ajax:{
      url:"{{ route('audit.attendance.late') }}",
      data:d=>{
        d.date=document.getElementById('date')?.value||'{{ $date }}';
        d.classroom_id=document.getElementById('classroom_id')?.value||'{{ $kelasId }}';
      }
    },
    columns:[
      {data:null,orderable:false,searchable:false,render:(d,t,r,meta)=>meta.row+meta.settings._iDisplayStart+1},
      {data:'nama',name:'nama'},
      {data:'kelas',name:'kelas'},
      {data:'terlambat_total',name:'terlambat_total',className:'text-end',searchable:false},
      {data:'last_at',name:'last_at',searchable:false}
    ],
    orderMulti:true
  });
  ['date','classroom_id'].forEach(id=>document.getElementById(id)?.addEventListener('change',()=>tblLate.ajax.reload()));
  document.querySelector('[data-bs-target="#tab-late"]')?.addEventListener('shown.bs.tab',()=>tblLate.columns.adjust());
  document.querySelectorAll('[data-bs-toggle="tab"]').forEach(btn=>btn.addEventListener('shown.bs.tab',()=>{dtRekap.columns.adjust();table.columns.adjust();}));
})();
</script>
@endpush
