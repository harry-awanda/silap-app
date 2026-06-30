@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

{{-- ================= FILTER CARD ================= --}}
<div class="card mb-3">
  <div class="card-body">

    {{-- Baris 1: Filter --}}
    <div class="row g-3 align-items-center">

      {{-- Filter Kelas --}}
      <div class="col-12 col-md-5">
        <label class="form-label mb-1 small text-muted">Kelas</label>
        <select id="filter-kelas" class="form-select select2">
          <option value="">Semua Kelas</option>
          @foreach($classrooms as $c)
            <option value="{{ $c->id }}">{{ $c->nama_kelas }}</option>
          @endforeach
        </select>
      </div>

      {{-- Filter Password --}}
      <div class="col-12 col-md-4">
        <label class="form-label mb-1 small text-muted">Password</label>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" id="filter-never-changed" value="1">
          <label class="form-check-label" for="filter-never-changed">
            Belum pernah diganti
          </label>
        </div>
      </div>

    </div>

    {{-- Baris 2: Reset --}}
    <div class="row mt-3">
      <div class="col-12 d-flex gap-2">
        <button id="btn-reset-filter" class="btn btn-outline-secondary btn-sm">
          <i class="bx bx-reset me-1"></i> Reset Filter
        </button>

        <a id="btn-export-excel" class="btn btn-success btn-sm" href="#">
          <i class="bx bx-download me-1"></i> Export Excel
        </a>
      </div>
    </div>    

  </div>
</div>
{{-- =============== END FILTER CARD =============== --}}

{{-- ================= TABLE CARD ================= --}}
<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ $title }}</h5>
    </div>

    {{-- Alert Password Sementara --}}
    @if (session('temp_password'))
      <div class="alert alert-info d-flex align-items-center gap-2 mt-3 mb-0">
        <strong>Password sementara:</strong>
        <code id="tempPass">{{ session('temp_password') }}</code>
        <button
          class="btn btn-sm btn-outline-secondary ms-auto"
          type="button"
          data-copy
          data-copy-target="#tempPass"
        >
          <i class="bx bx-copy me-1"></i> Copy
        </button>
      </div>
    @endif
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table id="dt-siswa" class="table table-striped table-hover w-100">
        <thead>
          <tr>
            <th style="width:50px;">#</th>
            <th style="width:80px;">Aksi</th>
            <th>Nama</th>
            <th>Kelas</th>
            <th>Username</th>
            <th>Password Diubah</th>
            <th>Status</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
{{-- =============== END TABLE CARD =============== --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

  const tbl = $('#dt-siswa').DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    lengthChange: true,
    pageLength: 10,
    order: [[2,'asc']], // Nama
    ajax: {
      url: "{{ route('admin.user-siswa.datatable') }}",
      type: "GET",
      data: function (d) {
        d.classroom_id  = $('#filter-kelas').val();
        d.never_changed = $('#filter-never-changed').is(':checked') ? 1 : 0;
      }
    },
    columns: [
      { data: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'actions', orderable: false, searchable: false },
      { data: 'name', name: 'users.name' },
      { data: 'kelas', name: 'classrooms.nama_kelas' },
      { data: 'username', name: 'users.username' },
      { data: 'password_changed_at', name: 'users.password_changed_at' },
      { data: 'online', orderable: false, searchable: false }
    ]
  });

  /* ================= EXPORT ================= */
  function updateExportUrl() {
    const classroomId  = $('#filter-kelas').val() || '';
    const neverChanged = $('#filter-never-changed').is(':checked') ? 1 : 0;

    const url = new URL("{{ route('admin.user-siswa.export') }}", window.location.origin);
    if (classroomId) url.searchParams.set('classroom_id', classroomId);
    if (neverChanged) url.searchParams.set('never_changed', '1');

    $('#btn-export-excel').attr('href', url.toString());
  }

  updateExportUrl();

  /* ================= FILTER EVENTS ================= */
  $('#filter-kelas, #filter-never-changed').on('change', function () {
    tbl.ajax.reload();
    updateExportUrl();
  });

  $('#btn-reset-filter').on('click', function () {
    $('#filter-never-changed').prop('checked', false);
    $('#filter-kelas').val('').trigger('change'); // select2 clear
    tbl.ajax.reload();
    updateExportUrl();
  });

  /* ================= COPY TO CLIPBOARD ================= */
  async function copyToClipboard(text) {
    if (!text) return false;
    text = String(text).trim();

    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return true;
      }

      const ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);

      ta.focus();
      ta.select();
      ta.setSelectionRange(0, ta.value.length);

      const ok = document.execCommand('copy');
      document.body.removeChild(ta);
      return ok;
    } catch (e) {
      return false;
    }
  }

  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;

    e.preventDefault();

    const text = btn.getAttribute('data-copy-text') || '';
    const ok = await copyToClipboard(text);

    const original = btn.innerHTML;
    btn.innerHTML = ok
      ? '<i class="bx bx-check me-1"></i>Tersalin'
      : '<i class="bx bx-x me-1"></i>Gagal';

    btn.classList.toggle('btn-success', ok);
    btn.classList.toggle('btn-danger', !ok);

    setTimeout(() => {
      btn.innerHTML = original;
      btn.classList.remove('btn-success','btn-danger');
    }, 1500);
  });

});
</script>
@endpush