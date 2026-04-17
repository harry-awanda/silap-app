@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.homeroom.index') }}">Penugasan Wali Kelas</a> /
  <span class="text-muted fw-light">Edit</span>
</h4>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.homeroom.update', $homeroom->id) }}">
      @csrf
      @method('PUT')

      <div class="row">
        <div class="col-lg-6 mx-auto">
          <div class="mb-3">
            <label class="form-label">Kelas</label>
            <select name="classroom_id" class="form-select select2" required>
              <option value="" disabled>-- Pilih Kelas --</option>
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}"
                  @selected(old('classroom_id', $homeroom->classroom_id) == $c->id)>
                  {{ $c->nama_kelas }} ({{ $c->tingkat }})
                </option>
              @endforeach
            </select>
            @error('classroom_id') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Guru (Wali Kelas)</label>
            <select name="guru_id" class="form-select select2" required>
              <option value="" disabled>-- Pilih Guru --</option>
              @foreach($gurus as $g)
                <option value="{{ $g->id }}"
                  @selected(old('guru_id', $homeroom->guru_id) == $g->id)>
                  {{ $g->nama_lengkap }}
                </option>
              @endforeach
            </select>
            @error('guru_id') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Mulai Penugasan</label>
            <input
              type="datetime-local"
              name="started_at"
              class="form-control"
              value="{{ old('started_at', optional($homeroom->started_at)->format('Y-m-d\TH:i')) }}"
            >
            @error('started_at') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="alert alert-info">
            <i class="bx bx-info-circle me-1"></i>
            <strong>Selesai Penugasan</strong> tetap dilakukan lewat tombol <strong>Akhiri</strong> di halaman index.
          </div>

          <hr>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.homeroom.index') }}" class="btn btn-secondary">
              <i class="bx bx-x me-1"></i> Batal
            </a>
            <button class="btn btn-primary">
              <i class="bx bx-save me-2"></i> Simpan Perubahan
            </button>
          </div>

        </div>
      </div>
    </form>
  </div>
</div>
@endsection
