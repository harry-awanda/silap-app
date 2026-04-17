@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> /
  <a href="{{route('admin.guru.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Tambah Data</span>
</h4>
<div class="row">
  <div class="col-md-12">
    <div class="card mb-4">
      <div class="col-lg-8 mx-auto">
        <div class="card-body">
          <form action="{{ route('admin.guru.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
              <div class="col md-6">
                <label for="nip" class="form-label">NIP / NRPTK / NRHS</label>
                <input type="text" class="form-control" name="nip" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="photo">Photo</label>
                <input type="file" name="photo" class="form-control">
              </div>
            </div>
            <div class="row">
              <div class="col md-6">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required>
              </div>
              <div class="col md-6">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" required>
              </div>
            </div>
            <div class="row">
              <div class="col md-6">
                <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                <input type="text" class="form-control" name="tempat_lahir" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control"/>
              </div>
            </div>
            
            <div class="row">
              <div class="col md-6">
                <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-select" data-allow-clear="true">
                  <option value="" selected disabled>Pilih Salah Satu</option>
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="kontak">Nomor Telepon / Whatsapp</label>
                  <div class="input-group">
                    <span class="input-group-text">+62</span>
                    <input type="text" name="kontak" class="form-control" placeholder="812 3456 7890" />
                  </div>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label" for="alamat">Alamat</label>
              <textarea name="alamat" class="form-control" rows="2" ></textarea>
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