@extends('auth.master')
@section('content')
<div class="card">
  <div class="card-body">
    <div class="app-brand justify-content-center">
      <a href="#" class="app-brand-link gap-2">
        <span class="app-brand-text demo text-uppercase fw-bolder">SILAP</span>
      </a>
    </div>
    <h4 class="mb-2">Sistem Informasi Layanan Akademik dan Pendidikan</h4>
    <form class="mb-3" action="{{ route('postLogin') }}" method="POST">
      @csrf
      <div class="mb-3">
        <label class="form-label" for="uname">Username</label>
        <input type="text" class="form-control" name="username"
        placeholder="Enter your username" value="{{ old('username') }}" autofocus required />
      </div>
      <div class="mb-3 form-password-toggle">
        <div class="d-flex justify-content-between">
          <label class="form-label" for="passwd">Password</label>
        </div>
        <div class="input-group input-group-merge">
          <input type="password" class="form-control" name="password"
          placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
          <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
      </div>
      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="remember">
          <label class="form-check-label" for="remember">Remember me</label>
        </div>
      </div>
      <div class="mb-3">
        <button class="btn btn-primary d-grid w-100" type="submit">Masuk</button>
      </div>
    </form>
  </div>
</div>
@endsection