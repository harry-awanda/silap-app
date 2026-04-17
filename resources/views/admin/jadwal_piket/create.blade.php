@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.jadwal-piket.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Tambah Data</span>
</h4>

<div class="card">
  <div class="card-body">
    <form action="{{ route('admin.jadwal-piket.store') }}" method="POST">
      @csrf
      <div class="row">
        <div class="col-lg-6 mx-auto">
          <div class="row g-3">

            {{-- Nama Guru --}}
            <div class="col-12">
              <label for="guru_id" class="form-label">Nama Guru</label>
              <select name="guru_id" id="guru_id" class="form-select select2" required>
                <option value="" selected disabled>Pilih Salah Satu</option>
                @foreach ($guru as $g)
                  <option value="{{ $g->id }}">{{ $g->nama_lengkap }}</option>
                @endforeach
              </select>
            </div>

            {{-- Hari Piket --}}
            <div class="col-12">
              <label for="hari_piket" class="form-label">Hari Piket</label>
              <select name="hari_piket" id="hari_piket" class="form-select" required>
                <option value="" selected disabled>Pilih Hari</option>
                @foreach (['Senin','Selasa','Rabu','Kamis','Jumat'] as $hari)
                  <option value="{{ $hari }}">{{ $hari }}</option>
                @endforeach
              </select>
            </div>

          </div>

          <hr>
          <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('admin.jadwal-piket.index') }}" class="btn btn-secondary">
              <i class="bx bx-x me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-1"></i> Simpan
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  $('.select2').select2({
    dropdownParent: $('.card-body')
  });
</script>
@endpush
