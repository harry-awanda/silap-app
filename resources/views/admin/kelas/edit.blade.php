@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.classrooms.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Edit Data</span>
</h4>

<div class="card">
  <div class="card-body">
    <div class="alert alert-info">
      Penetapan <strong>Wali Kelas</strong> dilakukan di menu <em>Homeroom Assignments</em>.
    </div>

    <form method="POST" action="{{ route('admin.classrooms.update', $classroom->id) }}">
      @csrf @method('PUT')
      <div class="row">
        <div class="col-lg-6 mx-auto">
          <div class="row g-3">
            <div class="mb-3">
              <label for="nama_kelas" class="form-label">Nama Kelas</label>
              <input type="text" class="form-control" name="nama_kelas" value="{{ $classroom->nama_kelas }}" required>
            </div>
            <div class="mb-3">
              <label for="tingkat" class="form-label">Tingkat</label>
              <select class="form-select" name="tingkat" required>
                <option value="" disabled>-- Tingkat --</option>
                <option value="10" {{ $classroom->tingkat == '10' ? 'selected' : '' }}>Kelas 10</option>
                <option value="11" {{ $classroom->tingkat == '11' ? 'selected' : '' }}>Kelas 11</option>
                <option value="12" {{ $classroom->tingkat == '12' ? 'selected' : '' }}>Kelas 12</option>
              </select>
            </div>
            {{-- tambahkan di atas input nama_kelas --}}
            <div class="mb-3">
              <label class="form-label">Term</label>
              <input type="text"
                  class="form-control"
                  value="{{ $classroom->term->year_start }}/{{ $classroom->term->year_end }} - {{ ucfirst($classroom->term->semester) }}"
                  disabled>
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
