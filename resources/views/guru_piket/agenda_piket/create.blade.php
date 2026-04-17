@extends('layouts.app')
@section('content')
<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> /
  <a href="{{route('agenda_piket.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Tambah Data</span>
</h4>

<form action="{{ route('agenda_piket.store') }}" method="POST">
  @csrf
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <div class="row g-3">

            <div class="col-12">
              <h6 class="mb-2">Guru yang Tidak Melaksanakan KBM</h6>
              <p class="text-muted small mb-2">Tambahkan baris untuk guru yang sakit/izin/alpa. Keterangan opsional.</p>

              {{-- ROWS CONTAINER --}}
              <div id="absensi-guru-rows">
                {{-- Row awal (pristine) di-render dari template via JS --}}
              </div>

              <div class="mt-2">
                <button type="button" class="btn btn-outline-primary" id="add-absensi-row">
                  <i class="bx bx-plus me-1"></i> Tambah
                </button>
              </div>
            </div>

            <div class="col-mb-3">
              <label for="guru_piket" class="form-label">Guru Piket</label>
              <select name="guru_piket[]" class="form-select select2" multiple data-allow-clear="true" required>
                @foreach($guruPiket as $piket)
                  <option value="{{ $piket->guru->id }}">{{ $piket->guru->nama_lengkap }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-mb-3">
              <label for="tanggal" class="form-label">Tanggal</label>
              <input type="date" name="tanggal" class="form-control" required>
            </div>

            <div class="col-md-12">
              <label for="kejadian_normal" class="form-label">Kejadian Normal</label>
              <textarea name="kejadian_normal" class="form-control"></textarea>
            </div>

            <div class="col-md-12">
              <label for="kejadian_masalah" class="form-label">Kejadian Masalah</label>
              <textarea name="kejadian_masalah" class="form-control"></textarea>
            </div>

            <div class="col-md-12">
              <label for="solusi" class="form-label">Solusi</label>
              <textarea name="solusi" class="form-control"></textarea>
            </div>

          </div>

          <hr>
          <div class="d-flex justify-content-end mt-3 gap-2">
            <a href="{{ route('agenda_piket.index') }}" class="btn btn-secondary">
              <i class="bx bx-x me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-2"></i>Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

{{-- TEMPLATE: row pristine tanpa Select2 ter-inisialisasi --}}
<template id="absensi-row-template">
  <div class="row g-2 align-items-end absensi-row">
    <div class="col-md-5">
      <label class="form-label">Guru</label>
      <select name="absensi_guru[0][guru_id]" class="form-select select2">
        <option value="">— Pilih Guru —</option>
        @foreach(\App\Models\Guru::orderBy('nama_lengkap')->get() as $g)
          <option value="{{ $g->id }}">{{ $g->nama_lengkap }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="absensi_guru[0][status]" class="form-select">
        <option value="">— Pilih —</option>
        <option value="sakit">Sakit</option>
        <option value="izin">Izin</option>
        <option value="alpa">Alpa</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Keterangan (opsional)</label>
      <input type="text" name="absensi_guru[0][keterangan]" class="form-control" placeholder="Misal: surat dokter">
    </div>
    <div class="col-md-1 d-grid">
      <button type="button" class="btn btn-outline-danger remove-row" disabled>
        <i class="bx bx-trash"></i>
      </button>
    </div>
  </div>
</template>
@endsection

@push('scripts')
<script>
  (function () {
    const $ = window.jQuery; // pastikan jQuery ada
    const container = document.getElementById('absensi-guru-rows');
    const addBtn = document.getElementById('add-absensi-row');
    const form = document.querySelector('form[action="{{ route('agenda_piket.store') }}"]');
    const tpl = document.getElementById('absensi-row-template');
    let idx = 0;

    function initSelect2(scope) {
      // Bersihkan jejak Select2 bila ada (safety guard jika HTML tersuntik ulang)
      scope.querySelectorAll('select.select2').forEach(el => {
        el.classList.remove('select2-hidden-accessible');
        el.removeAttribute('data-select2-id');
        el.removeAttribute('aria-hidden');
        el.removeAttribute('tabindex');
      });
      // Inisialisasi Select2
      $(scope).find('select.select2').select2({
        width: '100%',
        // dropdownParent: $('#modalId') // gunakan ini jika ada di modal
      });
    }

    function makeRow(i) {
      const node = tpl.content.firstElementChild.cloneNode(true);
      node.querySelectorAll('select, input').forEach(el => {
        if (el.name && /\[\d+\]/.test(el.name)) {
          el.name = el.name.replace(/\[\d+\]/, '[' + i + ']');
        }
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        if (el.tagName === 'INPUT') el.value = '';
      });
      // enable tombol hapus untuk row selain pertama
      node.querySelector('.remove-row').disabled = (i === 0);
      return node;
    }

    // render row pertama (pristine) lalu init select2
    const first = makeRow(idx++);
    container.appendChild(first);
    initSelect2(container);

    addBtn.addEventListener('click', () => {
      const row = makeRow(idx++);
      container.appendChild(row);
      initSelect2(row);
    });

    container.addEventListener('click', (e) => {
      const btn = e.target.closest('.remove-row');
      if (!btn) return;
      const rows = container.querySelectorAll('.absensi-row');
      if (rows.length > 1) btn.closest('.absensi-row').remove();
    });

    // Bersihkan baris kosong sebelum submit
    form.addEventListener('submit', () => {
      container.querySelectorAll('.absensi-row').forEach(row => {
        const guruId = row.querySelector('select[name*="[guru_id]"]')?.value?.trim();
        const status = row.querySelector('select[name*="[status]"]')?.value?.trim();
        const ket = row.querySelector('input[name*="[keterangan]"]')?.value?.trim();
        if (!guruId && !status && !ket) row.remove();
      });
    });
  })();
</script>
@endpush
