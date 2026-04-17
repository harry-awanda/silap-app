@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">Ganti Password</span>
</h4>

@if (session('warning'))
  <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <div class="card-body">
    <p class="mb-3">Anda diminta mengganti password sebelum melanjutkan.</p>

    <form method="POST" action="{{ route('password.first.update') }}">
      @csrf
      <div class="row">
        <div class="col-lg-5 mx-auto">
          <div class="row g-3">
            
            {{-- Password Baru --}}
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password">Password Baru</label>
              <div class="input-group">
                <input
                type="password"
                id="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="••••••••"
                required
                minlength="8"
                pattern="(?=.*[A-Z]).{8,}"
                title="Minimal 8 karakter & harus mengandung huruf kapital (A–Z)"
                autocomplete="new-password"
                autofocus
                />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
              </div>
              <div class="form-text">Syarat: minimal 8 karakter & ada minimal satu huruf kapital (A–Z).</div>
              @error('password')
              <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
            
            {{-- Konfirmasi Password --}}
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password_confirmation">Konfirmasi Password Baru</label>
              <div class="input-group">
                <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control @error('password_confirmation') is-invalid @enderror"
                placeholder="••••••••"
                required
                minlength="8"
                pattern="(?=.*[A-Z]).{8,}"
                title="Minimal 8 karakter & harus mengandung huruf kapital (A–Z)"
                autocomplete="new-password"
                />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
              </div>
              @error('password_confirmation')
              <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Tombol Simpan --}}
            <div class="d-flex justify-content-end mt-3 gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-lock-open me-1"></i> Simpan Password
              </button>
            </div>

          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
