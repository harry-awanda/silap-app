@extends('layouts.app')

@section('content')
@include('layouts.toasts')

@php
  // keep state dari query agar UX enak saat reload
  $qToTermId    = request('to_term_id', $toTerm?->id);
  $qPromoteKind = request('promote_kind', 'advance'); // advance|repeat
  $qTargetClass = request('target_classid');          // keep selected class (opsional)
  $qClassroomId = request('classroom_id', $current?->id);
  $indexRoute   = 'admin.siswa.promosi.index';
  $previewRoute = 'admin.siswa.promosi.preview';
@endphp

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.siswa.move.index') }}">Promosi Siswa</a> /
  <span class="text-muted fw-light">{{ $mode === 'promote' ? 'Naik Kelas' : 'Kelulusan' }}</span>
</h4>

{{-- CARD: FILTER / DROPDOWN --}}
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="fw-semibold">
      {{ $mode === 'promote' ? 'Pengaturan Promosi' : 'Pengaturan Kelulusan' }}
    </div>

    <span class="badge bg-label-secondary">
      Term Sumber: {{ $fromTermLabel }}
    </span>
  </div>

  <div class="card-body">
    @if($mode === 'promote')
      {{-- SATU FORM GET agar konsisten + keep state --}}
      <form class="row g-2 align-items-end" method="GET" action="{{ route($indexRoute, 'promote') }}">
        <div class="col-12 col-md-3">
          <label class="form-label">Kelas Sumber</label>
          <select class="form-select form-select" name="classroom_id" id="classroom_id" required onchange="this.form.submit()">
            @foreach(($classes ?? collect([$current])) as $c)
              <option value="{{ $c->id }}" {{ (string)$qClassroomId === (string)$c->id ? 'selected' : '' }}>
                {{ $c->tingkat }} - {{ $c->nama_kelas }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Term Tujuan</label>
          <select class="form-select form-select" name="to_term_id" id="to_term_id" required onchange="this.form.submit()">
            <option disabled {{ !$qToTermId ? 'selected' : '' }}>— Pilih —</option>
            @foreach($toTerms as $t)
              <option value="{{ $t->id }}" {{ (string)$qToTermId === (string)$t->id ? 'selected' : '' }}>
                {{ $t->year_start }}/{{ $t->year_end }} - {{ ucfirst($t->semester) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label">Jenis Promosi</label>
          <select class="form-select form-select" name="promote_kind" id="promote_kind" required onchange="this.form.submit()">
            <option value="advance" {{ $qPromoteKind === 'advance' ? 'selected' : '' }}>Naik Kelas</option>
            <option value="repeat"  {{ $qPromoteKind === 'repeat'  ? 'selected' : '' }}>Tinggal Kelas</option>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Kelas Tujuan</label>
          <select class="form-select form-select" name="target_classid" id="target_classid" required onchange="this.form.submit()">
            <option disabled {{ empty($targetClasses) ? 'selected' : '' }}>— Pilih —</option>
            @foreach($targetClasses as $c)
              <option value="{{ $c->id }}" {{ (string)$qTargetClass === (string)$c->id ? 'selected' : '' }}>
                {{ $c->tingkat }} - {{ $c->nama_kelas }}
              </option>
            @endforeach
          </select>
          @if($qToTermId && $targetClasses->isEmpty())
            <small class="text-muted d-block mt-1">
              Tidak ada kelas tujuan untuk pilihan term & jenis promosi ini.
            </small>
          @endif
        </div>

        <div class="col-12 col-md-1 d-grid">
          <button type="submit" class="btn btn-label-primary">
            Terapkan
          </button>
        </div>
      </form>

      @if($toTermLabel)
        <div class="mt-3 d-flex gap-2 flex-wrap">
          <span class="badge bg-label-primary">Term Tujuan: {{ $toTermLabel }}</span>
          <span class="badge bg-label-warning">Jenis: {{ $qPromoteKind === 'repeat' ? 'Tinggal Kelas' : 'Naik Kelas' }}</span>
        </div>
      @endif

    @else
      {{-- MODE GRADUATE --}}
      <form class="row g-2 align-items-end" method="GET" action="{{ route($indexRoute, 'graduate') }}">
        <div class="col-12 col-md-4">
          <label class="form-label">Kelas Sumber</label>
          <select class="form-select form-select" name="classroom_id" id="classroom_id" required onchange="this.form.submit()">
            @foreach(($classes ?? collect([$current])) as $c)
              <option value="{{ $c->id }}" {{ (string)$qClassroomId === (string)$c->id ? 'selected' : '' }}>
                {{ $c->tingkat }} - {{ $c->nama_kelas }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Angkatan</label>
          <input
            type="number"
            class="form-control form-control-sm"
            id="angkatan"
            value="{{ old('angkatan', date('Y')) }}"
            min="2000"
            max="{{ date('Y')+1 }}"
            required
          >
          <small class="text-muted d-block mt-1">Contoh: 2025</small>
        </div>
      </form>
    @endif
  </div>
</div>

{{-- CARD: LIST SISWA --}}
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="fw-semibold">
      {{ $mode === 'promote' ? 'Pilih Siswa untuk Diproses' : 'Pilih Siswa untuk Kelulusan' }}
    </div>

    @if($mode === 'promote')
      <span class="badge bg-label-secondary">
        Pastikan Term Tujuan, Jenis, dan Kelas Tujuan sudah dipilih.
      </span>
    @endif
  </div>

  <div class="card-body">
    <form id="form-next" action="{{ route($previewRoute, $mode) }}" method="POST">
      @csrf

      {{-- hidden inputs diisi via JS sebelum submit --}}
      <input type="hidden" name="classroom_id" id="h_classroom_id" value="{{ $current?->id }}">
      <input type="hidden" name="to_term_id" id="h_to_term_id">
      <input type="hidden" name="promote_kind" id="h_promote_kind">
      <input type="hidden" name="target_classid" id="h_target_classid">
      <input type="hidden" name="angkatan" id="h_angkatan">

      <div class="table-responsive text-nowrap table-hover">
        <table class="table datatable" id="tbl-siswa">
          <thead>
            <tr>
              <th style="width:48px"><input type="checkbox" id="check-all"></th>
              <th>#</th>
              <th>NIS</th>
              <th>Nama</th>
              <th>JK</th>
            </tr>
          </thead>
          <tbody>
            @foreach($siswa as $row)
              <tr>
                <td><input type="checkbox" class="cb" value="{{ $row->id }}"></td>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row->nis }}</td>
                <td class="text-uppercase">{{ $row->nama_lengkap }}</td>
                <td>{{ $row->jenis_kelamin }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-3 d-flex justify-content-end">
        <button type="submit" id="btn-next" class="btn btn-primary" disabled>
          Lanjutkan
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const dt = new DataTable('.datatable', {
    perPage: 25,
    order: [[3, 'asc']]
  });

  const master  = document.getElementById('check-all');
  const btnNext = document.getElementById('btn-next');

  function selectedCount() {
    return document.querySelectorAll('#tbl-siswa tbody input.cb:checked').length;
  }

  function syncButton() {
    const hasSelection = selectedCount() > 0;
    const mode = @json($mode);

    if (mode === 'promote') {
      const toTermEl = document.getElementById('to_term_id');
      const kindEl   = document.getElementById('promote_kind');
      const classEl  = document.getElementById('target_classid');

      const okToTerm = toTermEl && !!toTermEl.value;
      const okKind   = kindEl   && !!kindEl.value;
      const okClass  = classEl  && !!classEl.value;

      btnNext.disabled = !(hasSelection && okToTerm && okKind && okClass);
    } else {
      const angkatan = document.getElementById('angkatan');
      const okAngkatan = angkatan && !!angkatan.value;
      btnNext.disabled = !(hasSelection && okAngkatan);
    }
  }

  master?.addEventListener('change', () => {
    document.querySelectorAll('#tbl-siswa tbody input.cb').forEach(cb => {
      cb.checked = master.checked;
    });
    syncButton();
  });

  document.getElementById('tbl-siswa')?.addEventListener('change', function (e) {
    if (e.target && e.target.classList.contains('cb')) syncButton();
  });

  dt.on('datatable.page',   () => { if (master) master.checked = false; syncButton(); });
  dt.on('datatable.sort',   () => { if (master) master.checked = false; syncButton(); });
  dt.on('datatable.search', () => { if (master) master.checked = false; syncButton(); });
  dt.on('datatable.perpage',() => { if (master) master.checked = false; syncButton(); });

  document.getElementById('form-next')?.addEventListener('submit', function (e) {
    // bersihkan hidden siswa_ids[] sebelumnya
    this.querySelectorAll('input[name="siswa_ids[]"]').forEach(n => n.remove());

    // isi siswa_ids[]
    document.querySelectorAll('#tbl-siswa tbody input.cb:checked').forEach(cb => {
      const h = document.createElement('input');
      h.type = 'hidden'; h.name = 'siswa_ids[]'; h.value = cb.value;
      this.appendChild(h);
    });

    const mode = @json($mode);

    if (mode === 'promote') {
      const toTermEl = document.getElementById('to_term_id');
      const kindEl   = document.getElementById('promote_kind');
      const classEl  = document.getElementById('target_classid');

      if (!toTermEl?.value || !kindEl?.value || !classEl?.value) { e.preventDefault(); return; }

      document.getElementById('h_to_term_id').value = toTermEl.value;
      document.getElementById('h_promote_kind').value = kindEl.value;
      document.getElementById('h_target_classid').value = classEl.value;
    } else {
      const angkatanEl = document.getElementById('angkatan');
      if (!angkatanEl?.value) { e.preventDefault(); return; }
      document.getElementById('h_angkatan').value = angkatanEl.value;
    }
  });

  // sync on changes
  const targetClass = document.getElementById('target_classid');
  if (targetClass) targetClass.addEventListener('change', syncButton);

  const promoteKind = document.getElementById('promote_kind');
  if (promoteKind) promoteKind.addEventListener('change', syncButton);

  const toTerm = document.getElementById('to_term_id');
  if (toTerm) toTerm.addEventListener('change', syncButton);

  const angkatan = document.getElementById('angkatan');
  if (angkatan) angkatan.addEventListener('input', syncButton);

  syncButton();
});
</script>
@endpush
