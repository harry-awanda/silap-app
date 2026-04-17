@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('pelanggaranSiswa.index') }}">Pelanggaran Siswa</a> /
  <span class="text-muted fw-light">Tambah</span>
</h4>

<div class="card">
  <div class="col-lg-8 mx-auto">
    <div class="card-body">

      {{-- =========================
          FILTER KELAS (OPS A) - hanya kesiswaan/guru_bk
          method GET ke create route => reload
      ========================= --}}
      @if(auth()->user()->hasAnyRole(['kesiswaan','guru_bk']))
        <form method="GET" action="{{ route('pelanggaranSiswa.create') }}" class="row g-3 mb-2">
          <div class="col-md-8">
            <label class="form-label">Kelas</label>
            <select name="classroom_id" class="form-select select2" data-placeholder="Pilih kelas..." required>
              <option value=""></option>
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}" @selected((int)$selectedClassroomId === (int)$c->id)>
                  {{ $c->nama_kelas }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-secondary w-100">
              <i class="bx bx-filter-alt me-1"></i> Terapkan
            </button>
          </div>
        </form>

        <div class="alert alert-info py-2 mb-4">
          Pilih kelas dulu, lalu halaman akan memuat daftar siswa pada kelas tersebut.
        </div>
      @endif

      {{-- =========================
          FORM STORE
      ========================= --}}
      <form method="POST" action="{{ route('pelanggaranSiswa.store') }}" class="row g-3">
        @csrf

        {{-- Jika kesiswaan/guru_bk: kirim classroom_id agar store bisa validasi pivot --}}
        @if(auth()->user()->hasAnyRole(['kesiswaan','guru_bk']))
          <input type="hidden" name="classroom_id" value="{{ (int)$selectedClassroomId }}">
        @endif

        {{-- Siswa (Select2 biasa) --}}
        <div class="col-md-12">
          <label class="form-label">Siswa</label>

          @if(auth()->user()->hasAnyRole(['kesiswaan','guru_bk']) && (int)$selectedClassroomId <= 0)
            <select class="form-select" disabled>
              <option>Pilih kelas terlebih dahulu…</option>
            </select>
          @else
            <select name="siswa_id" class="form-select select2" data-placeholder="Pilih siswa..." required>
              <option value=""></option>
              @foreach($siswa as $s)
                <option value="{{ $s->id }}" @selected(old('siswa_id') == $s->id)>
                  {{ $s->nama_lengkap }} ({{ $s->nis }})
                </option>
              @endforeach
            </select>
          @endif

          @error('siswa_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
          <label class="form-label">Tanggal Pelanggaran</label>
          <input type="date" name="tanggal_pelanggaran" class="form-control"
                 value="{{ old('tanggal_pelanggaran', now()->toDateString()) }}" required>
          @error('tanggal_pelanggaran')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- Status & Tindakan (kesiswaan / guru BK / wali kelas) --}}
        @if(auth()->user()->hasAnyRole(['kesiswaan','guru_bk','wali_kelas']))
          @php
            $statusVal   = old('status');
            $tindakanVal = old('tindakan');
          @endphp
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">— Pilih —</option>
              <option value="diproses" @selected($statusVal==='diproses')>Diproses</option>
              <option value="selesai"  @selected($statusVal==='selesai')>Selesai</option>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Tindakan</label>
            <select name="tindakan" class="form-select">
              <option value="">— Pilih —</option>
              <option value="pembinaan_wali_kelas"     @selected($tindakanVal==='pembinaan_wali_kelas')>Pembinaan Wali Kelas</option>
              <option value="pembinaan_guru_bk"        @selected($tindakanVal==='pembinaan_guru_bk')>Pembinaan Guru BK</option>
              <option value="pembinaan_kepala_sekolah" @selected($tindakanVal==='pembinaan_kepala_sekolah')>Pembinaan Kepala Sekolah</option>
            </select>
          </div>
        @endif

        <div class="col-md-12">
          <label class="form-label">Pelanggaran</label>
          <select name="pelanggaran[]" class="form-select select2" multiple
                  data-placeholder="Pilih jenis pelanggaran..." required>
            @foreach($pelanggaranByJenis as $jenis => $items)
              <optgroup label="{{ strtoupper($jenis) }}">
                @foreach($items as $p)
                  <option value="{{ $p->id }}" @selected(in_array($p->id, old('pelanggaran', [])))>
                    {{ $p->nama }}
                  </option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
          @error('pelanggaran')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
          <label class="form-label">Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan') }}</textarea>
        </div>

        {{-- Catatan per role --}}
        @if(auth()->user()->hasRole('wali_kelas'))
          <div class="col-12">
            <label class="form-label">Catatan Wali Kelas</label>
            <textarea name="catatan_waliKelas" class="form-control" rows="3">{{ old('catatan_waliKelas') }}</textarea>
          </div>
        @elseif(auth()->user()->hasRole('kesiswaan'))
          <div class="col-12">
            <label class="form-label">Catatan Kesiswaan</label>
            <textarea name="catatan_kesiswaan" class="form-control" rows="3">{{ old('catatan_kesiswaan') }}</textarea>
          </div>
        @elseif(auth()->user()->hasRole('guru_bk'))
          <div class="col-12">
            <label class="form-label">Catatan Guru BK</label>
            <textarea name="catatan_guruBK" class="form-control" rows="3">{{ old('catatan_guruBK') }}</textarea>
          </div>
        @endif

        <hr>
        <div class="d-flex justify-content-end mt-3 gap-2">
          <a href="{{ route('pelanggaranSiswa.index') }}" class="btn btn-secondary">
            <i class="bx bx-x me-1"></i> Batal
          </a>
          <button type="submit" class="btn btn-primary"
                  @if(auth()->user()->hasAnyRole(['kesiswaan','guru_bk']) && (int)$selectedClassroomId <= 0) disabled @endif>
            <i class="bx bx-save me-2"></i> Simpan
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  if (!window.jQuery || !$.fn || !$.fn.select2) return;

  $('.select2').each(function(){
    const $el = $(this);
    if (!$el.data('select2')) {
      $el.select2({ allowClear: true, width: '100%' });
    }
  });
})();
</script>
@endpush