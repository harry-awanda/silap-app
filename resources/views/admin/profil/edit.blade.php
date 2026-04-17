@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<!-- Content -->
<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

<form method="POST" action="{{ route('admin.profil.update') }}">
  @csrf
  @method('PUT')

  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="nama_sekolah" class="form-label">Nama Sekolah</label>
              <input id="nama_sekolah" type="text" class="form-control" name="nama_sekolah"
              value="{{ old('nama_sekolah', $profil->nama_sekolah) }}" required>
            </div>

            <div class="col-md-6">
              <label for="npsn" class="form-label">NPSN</label>
              <input id="npsn" type="text" class="form-control" name="npsn"
              value="{{ old('npsn', $profil->npsn) }}" required>
            </div>

            <div class="col-md-6">
              <label for="nomor_telepon" class="form-label">Nomor Telepon</label>
              <input id="nomor_telepon" type="text" class="form-control" name="nomor_telepon"
              value="{{ old('nomor_telepon', $profil->nomor_telepon) }}">
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label">Email</label>
              <input id="email" type="email" class="form-control" name="email"
              value="{{ old('email', $profil->email) }}">
            </div>

            <div class="col-12">
              <label for="alamat" class="form-label">Alamat</label>
              <textarea id="alamat" name="alamat" class="form-control" rows="2">{{ old('alamat', $profil->alamat) }}</textarea>
            </div>

            <div class="col-md-6">
              <label for="kepala_sekolah_id" class="form-label">Kepala Sekolah</label>
              <select id="kepala_sekolah_id" name="kepala_sekolah_id" class="form-select select2" data-allow-clear="true">
                <option value="">Pilih Kepala Sekolah</option>
                @foreach($kepalaSekolahOptions as $id => $nama_lengkap)
                  <option value="{{ $id }}"
                    {{ (string) old('kepala_sekolah_id', $profil->kepala_sekolah_id) === (string) $id ? 'selected' : '' }}>
                    {{ $nama_lengkap }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label for="kesiswaan_id" class="form-label">Kesiswaan</label>
              <select id="kesiswaan_id" name="kesiswaan_id" class="form-select select2">
                <option value="">Pilih Kesiswaan</option>
                @foreach($kesiswaanOptions as $id => $nama_lengkap)
                  <option value="{{ $id }}"
                    {{ (string) old('kesiswaan_id', $profil->kesiswaan_id) === (string) $id ? 'selected' : '' }}>
                    {{ $nama_lengkap }}
                  </option>
                @endforeach
              </select>
            </div>
          </div> {{-- /.row g-3 --}}
          <hr>
          <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-1"></i> Simpan
            </button>
          </div>
        </div> {{-- /.col --}}
      </div> {{-- /.row --}}
    </div> {{-- /.card-body --}}

  </div> {{-- /.card --}}
</form>
@endsection
