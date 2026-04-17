@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.homeroom.index') }}">Penugasan Wali Kelas</a> /
  <span class="text-muted fw-light">Tambah</span>
</h4>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.homeroom.store') }}">
      @csrf
      <div class="row">
        <div class="col-lg-6 mx-auto">
          <div class="mb-3">
            <label class="form-label">Kelas</label>
            <select name="classroom_id" class="form-select select2" required>
              <option value="" disabled selected>-- Pilih Kelas --</option>
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}">{{ $c->nama_kelas }} ({{ $c->tingkat }})</option>
              @endforeach
            </select>
            @error('classroom_id') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Guru (Wali Kelas)</label>
            <select name="guru_id" class="form-select select2" required>
              <option value="" disabled selected>-- Pilih Guru --</option>
              @foreach($gurus as $g)
                <option value="{{ $g->id }}">{{ $g->nama_lengkap }}</option>
              @endforeach
            </select>
            @error('guru_id') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Mulai Penugasan</label>
            <input type="datetime-local" name="started_at" class="form-control">
            @error('started_at') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <hr>
          
          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.homeroom.index') }}" class="btn btn-secondary">
              <i class="bx bx-x me-1"></i> Batal
            </a>
            <button class="btn btn-primary"><i class="bx bx-save me-2"></i> Simpan</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
