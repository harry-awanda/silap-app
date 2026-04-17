@extends('layouts.app')
@section('content')
@include('layouts.toasts')
<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> /
  <a href="{{route('admin.guru.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Edit Data</span>
</h4>
<div class="row">
  <div class="col-md-12">
    <div class="card mb-4">
      <div class="col-lg-8 mx-auto">
        <div class="card-body">
          <form action="{{ route('admin.guru.update', $guru->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
              <div class="col md-6">
                <label for="nip" class="form-label">NIP / NRPTK / NRHS</label>
                <input type="text" class="form-control" name="nip" value="{{ $guru->nip }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="photo">Photo</label>
                <input type="file" name="photo" class="form-control">
                @if($guru->photo)
                <small class="text-muted">Current Photo: {{ $guru->photo }}</small>
                @endif
              </div>
            </div>
            <div class="row">
              <div class="col md-6">
                <!-- <label for="username" class="form-label">Username</label> -->
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <!-- <input type="text" class="form-control" name="username" value="{{ $guru->username }}" required> -->
                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                value="{{ old('username', optional($guru->user)->username) }}" placeholder="username login" required />
                @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                  Hanya huruf/angka/titik/garis-bawah/strip. Spasi akan dihapus otomatis di server.
                </div>
              </div>
              <div class="col md-6">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" value="{{ $guru->nama_lengkap }}" required>
              </div>
            </div>
            
            <div class="row">
              <div class="col md-6">
                <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                <input type="text" class="form-control" name="tempat_lahir" value="{{ $guru->tempat_lahir }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="{{ $guru->tanggal_lahir }}" class="form-control"/>
              </div>
            </div>
            
            <div class="row">
              <div class="col md-6">
                <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-select" data-allow-clear="true">
                  <option value="" disabled>Pilih Salah Satu</option>
                  <option value="L" {{ $guru->jenis_kelamin == 'L' ? 'selected' : '' }}>Laki-laki</option>
                  <option value="P" {{ $guru->jenis_kelamin == 'P' ? 'selected' : '' }}>Perempuan</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="tanggal_lahir">Nomor Telepon / Whatsapp</label>
                  <div class="input-group">
                    <span class="input-group-text">+62</span>
                    <input type="text" name="kontak" class="form-control" value="{{ $guru->kontak }}" />
                  </div>
              </div>
            </div>
            
            <div class="col-12">
              <label class="form-label" for="alamat">Alamat</label>
              <textarea name="alamat" class="form-control" rows="2" >{{ $guru->alamat }}</textarea>
            </div>

            <hr>
            <div class="d-flex justify-content-end mt-3 gap-2">
              <a href="{{ route('admin.guru.index') }}" class="btn btn-secondary">
                <i class="bx bx-x me-1"></i> Batal
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-2"></i> Simpan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection