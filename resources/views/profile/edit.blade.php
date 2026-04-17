@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">SILAP /</span> {{ $title }}
</h4>

<div class="row">
  <div class="col-md-12">

    <div class="card mb-4">
      <div class="col-lg-8 mx-auto">

        {{-- =================== FOTO PROFIL =================== --}}
        <h5 class="card-header">Foto Profil</h5>
        <div class="card-body">
          <div class="d-flex align-items-start align-items-sm-center gap-4">
            <img
              id="avatarPreview"
              src="{{ photo_url($user) }}"
              alt="user-avatar"
              class="d-block rounded"
              height="100"
              width="100"
            />

            @unless(auth()->user()->hasRole('siswa'))
              <form id="photoForm"
                    action="{{ route('profile.updatePhoto') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="d-flex flex-column align-items-start gap-2">
                @csrf

                <input type="file" id="upload" name="photo" accept="image/png, image/jpeg" hidden />
                <input type="hidden" name="cropped" id="croppedInput">

                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-primary" id="btnChooseFile">
                    Pilih Foto
                  </button>
                  <button type="button" class="btn btn-label-secondary" id="btnResetFile">
                    Reset
                  </button>
                </div>

                <button type="submit" class="btn btn-primary" id="btnSaveCropped" disabled>
                  Simpan Foto
                </button>

                <small class="text-muted">JPG/PNG • Maks 2 MB • Crop 1:1</small>
              </form>
            @else
              <div class="text-muted">
                Siswa tidak diperbolehkan mengubah foto profil.
                <br>Silakan hubungi wali kelas atau admin.
              </div>
            @endunless
          </div>
        </div>

        <hr class="my-0">

        {{-- =================== AKUN & PROFIL =================== --}}
        <div class="card-body">
          <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">

              {{-- ====== USERNAME ====== --}}
              <div class="mb-3 col-md-6">
                <label class="form-label">Username</label>
                @if($siswa)
                  <input class="form-control" type="text" value="{{ $user->username }}" readonly>
                  <small class="text-muted">Username tidak dapat diubah.</small>
                @else
                  <input class="form-control"
                         type="text"
                         name="username"
                         value="{{ old('username', $user->username) }}">
                @endif
              </div>

              {{-- ====== NAMA LENGKAP ====== --}}
              <div class="mb-3 col-md-6">
                <label class="form-label">Nama Lengkap</label>
                <input class="form-control"
                       type="text"
                       name="nama_lengkap"
                       value="{{ old('nama_lengkap', $guru->nama_lengkap ?? $siswa->nama_lengkap ?? $user->name) }}">
              </div>

              {{-- ================= GURU SAJA ================= --}}
              @if($guru)
                <div class="mb-3 col-md-6">
                  <label class="form-label">NIP / NRPTK / NRHS</label>
                  <input class="form-control" type="text" name="nip" value="{{ old('nip', $guru->nip) }}">
                </div>

                <div class="mb-3 col-md-6">
                  <label class="form-label">Tempat Lahir</label>
                  <input class="form-control" type="text" name="tempat_lahir"
                         value="{{ old('tempat_lahir', $guru->tempat_lahir) }}">
                </div>

                <div class="mb-3 col-md-6">
                  <label class="form-label">Tanggal Lahir</label>
                  <input class="form-control" type="date" name="tanggal_lahir"
                         value="{{ old('tanggal_lahir', $guru->tanggal_lahir) }}">
                </div>

                <div class="mb-3 col-md-6">
                  <label class="form-label">Nomor Telepon / Whatsapp</label>
                  <div class="input-group">
                    <span class="input-group-text">+62</span>
                    <input class="form-control" type="text" name="kontak"
                           value="{{ old('kontak', $guru->kontak) }}">
                  </div>
                </div>

                <div class="mb-3 col-md-12">
                  <label class="form-label">Alamat</label>
                  <textarea class="form-control" name="alamat" rows="2">{{ old('alamat', $guru->alamat) }}</textarea>
                </div>
              @endif
            </div>

            <div class="mt-2">
              <button type="submit" class="btn btn-primary me-2">Simpan Perubahan</button>
              <button type="reset" class="btn btn-label-secondary">Batal</button>
            </div>
          </form>
        </div>

        <hr class="my-0">

        {{-- =================== PASSWORD =================== --}}
        <div class="card-body">
          <form action="{{ route('profile.updatePassword') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
              <div class="mb-3 col-md-6">
                <label class="form-label">Password Saat Ini</label>
                <input class="form-control" type="password" name="current_password">
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label">Password Baru</label>
                <input class="form-control" type="password" name="new_password">
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input class="form-control" type="password" name="new_password_confirmation">
              </div>

              <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">Simpan Password</button>
                <button type="reset" class="btn btn-label-secondary">Batal</button>
              </div>
            </div>
          </form>
        </div>

      </div>
    </div>

  </div>
</div>
@endsection
