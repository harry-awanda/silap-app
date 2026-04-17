@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.classrooms.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Tambah Data</span>
</h4>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.classrooms.store') }}">
      @csrf
      <div class="row">
        <div class="col-lg-6 mx-auto">
          <div class="row g-3">
            <div class="mb-3">
              <label for="nama_kelas" class="form-label">Nama Kelas</label>
              <input type="text" class="form-control" name="nama_kelas" id="nama_kelas" required>
            </div>
            <div class="mb-3">
              <label for="tingkat" class="form-label">Tingkat</label>
              <select class="form-select" name="tingkat" required>
                <option value="" selected disabled>-- Tingkat --</option>
                <option value="10">Kelas 10</option>
                <option value="11">Kelas 11</option>
                <option value="12">Kelas 12</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Term</label>
              <select class="form-select select2" name="term_id" required>
                <option value="" disabled {{ empty(old('term_id', $activeTermId)) ? 'selected' : '' }}>-- Pilih Term --</option>
                @foreach($terms as $t)
                  <option value="{{ $t->id }}" {{ (string)old('term_id', $activeTermId) === (string)$t->id ? 'selected' : '' }}>
                    {{ $t->year_start }}/{{ $t->year_end }} - {{ ucfirst($t->semester) }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <hr>
          <div class="d-flex justify-content-end mt-3 gap-2">
            <a href="{{ route('admin.classrooms.index') }}" class="btn btn-secondary">
              <i class="bx bx-x me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-2"></i> Simpan
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
