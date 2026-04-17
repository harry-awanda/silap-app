@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">Pelanggaran Siswa</span>
</h4>

<div class="card mb-4">
  <div class="card-header d-flex flex-wrap gap-2 align-items-end justify-content-between">
    <h5 class="mb-0">Pelanggaran Siswa</h5>
    @unless(auth()->user()->hasRole('admin'))
      <a href="{{ route('pelanggaranSiswa.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-2"></i> Tambah Pelanggaran
      </a>
    @endunless
  </div>

  {{-- Filter bar hanya untuk admin, guru BK, kesiswaan --}}
  @if(auth()->user()->hasAnyRole(['guru_bk','kesiswaan','admin']))
    <div class="card-body pb-1">
      <form id="filter-form" class="row g-2 align-items-end" onsubmit="return false;">
        <div class="col-md-4">
          <label class="form-label mb-1">Filter Kelas</label>
          <select name="classroom_id" id="classroom-filter"
                  class="form-select select2"
                  data-allow-clear="true"
                  data-placeholder="Pilih kelas...">
            <option value=""></option>
            @foreach($classrooms as $c)
              <option value="{{ $c->id }}">{{ $c->nama_kelas }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Status</label>
          <select name="status" id="status-filter" class="form-select">
            <option value="">— Semua —</option>
            <option value="diproses">Diproses</option>
            <option value="selesai">Selesai</option>
          </select>
        </div>
        <div class="col-md-5 d-flex gap-2">
          <button id="btn-apply" class="btn btn-secondary mt-3">Terapkan</button>
          <button id="btn-reset" class="btn btn-outline-secondary mt-3">Reset</button>
        </div>
      </form>
    </div>
  @endif

  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover">
      <table class="table" id="tbl-pelanggaran">
        <thead>
          <tr>
            <th class="text-center" style="width:56px">#</th>
            <th class="text-center" style="width:64px">Pilihan</th>
            <th style="width:110px">Tanggal</th>
            <th>Siswa</th>
            <th style="width:140px">Kelas</th>
            <th style="width:110px">Status</th>
            <th style="width:200px">Tindakan</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

@include('pelanggaranSiswa.partials.modal-detail')
@endsection

@push('scripts')
<script>
(function() {
  const table = $('#tbl-pelanggaran').DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    ordering: true,
    lengthChange: true,
    ajax: {
      url: "{{ route('pelanggaranSiswa.datatable') }}",
      data: function (d) {
        d.classroom_id = $('#classroom-filter').val() || '';
        d.status       = $('#status-filter').val() || '';
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false },
      { data: 'actions', name: 'actions', className: 'text-center', orderable: false, searchable: false },
      { data: 'tanggal',     name: 'tanggal_pelanggaran' },
      { data: 'siswa_nama',  name: 'siswa.nama_lengkap' },
      { data: 'kelas',       name: 'siswa.classroom.nama_kelas' },
      { data: 'status_badge', name: 'status' },
      { data: 'tindakan_text', name: 'tindakan' },
    ],
    language: {
      emptyTable: 'Data pelanggaran kosong.',
      processing: 'Memuat...',
      search: 'Cari:',
      lengthMenu: 'Tampil _MENU_ data',
      info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
      infoEmpty: 'Menampilkan 0 data',
      infoFiltered: '(disaring dari _MAX_ total data)',
      paginate: { first: 'Pertama', last: 'Terakhir', next: '›', previous: '‹' }
    }
  });

  // Filter
  $('#btn-apply').on('click', function(){ table.ajax.reload(null, false); });
  $('#btn-reset').on('click', function(){
    $('#classroom-filter').val('').trigger('change');
    $('#status-filter').val('');
    table.ajax.reload(null, false);
  });
  $('#classroom-filter').on('change', () => table.ajax.reload(null, false));
  $('#status-filter').on('change',   () => table.ajax.reload(null, false));

  // Handler tombol Detail
  $(document).on('click', '.btn-detail', function(e) {
    e.preventDefault();
    const url = $(this).data('url');
    const modal = new bootstrap.Modal(document.getElementById('pelanggaranDetailModal'));
    const $loading = $('#detail-loading');
    const $content = $('#detail-content');

    // reset
    $loading.removeClass('d-none');
    $content.addClass('d-none');
    modal.show();

    $.get(url)
      .done(function(res) {
        $('#d-tanggal').text(res.tanggal || '—');
        $('#d-siswa').text(res.siswa?.nama || '—');
        $('#d-nis').text(res.siswa?.nis || '—');
        $('#d-kelas').text(res.siswa?.kelas || '—');
        if (Array.isArray(res.pelanggaran) && res.pelanggaran.length) {
          const html = '<ol class="mb-0 ps-3">' +
            res.pelanggaran.map(p => `<li>${escapeHtml(p.nama)} <span class="text-muted">(${escapeHtml(p.jenis)})</span></li>`).join('') +
            '</ol>';
          $('#d-pelanggaran').html(html);
        } else {
          $('#d-pelanggaran').text('—');
        }

        const badge = `<span class="badge ${res.status?.badge_class || 'bg-label-secondary'}">${escapeHtml(res.status?.text || '—')}</span>`;
        $('#d-status').html(badge);
        $('#d-tindakan').text(res.tindakan || '—');
        $('#d-keterangan').text(res.keterangan || '—');
        $('#d-cat-wk').text(res.catatan?.wali_kelas || '—');
        $('#d-cat-ks').text(res.catatan?.kesiswaan  || '—');
        $('#d-cat-bk').text(res.catatan?.guru_bk    || '—');
        $('#d-created').text(res.created_at || '—');
        $('#d-updated').text(res.updated_at || '—');

        $loading.addClass('d-none');
        $content.removeClass('d-none');
      })
      .fail(function() {
        $loading.addClass('d-none');
        $content.removeClass('d-none').html('<div class="text-danger">Gagal memuat detail</div>');
      });
  });

  function escapeHtml(str) {
    if (typeof str !== 'string') return str;
    return str.replace(/[&<>"'`=\/]/g, s => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'
    }[s]));
  }
})();
</script>
@endpush
