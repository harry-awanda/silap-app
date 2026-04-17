@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.users.index') }}">Manajemen User</a> /
  <span class="text-muted fw-light">Tambah</span>
</h4>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.users.store') }}">
      @csrf

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nama</label>
          <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
          @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" value="{{ old('username') }}" class="form-control" required>
          @error('username')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Email (opsional)</label>
          <input type="email" name="email" value="{{ old('email') }}" class="form-control">
          @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
          @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Konfirmasi Password</label>
          <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Roles</label>
          <select name="roles[]" class="form-select" multiple required>
            @foreach ($allRoles as $r)
              <option value="{{ $r->name }}" @selected(collect(old('roles',[]))->contains($r->name))>
                {{ $r->name }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Tahan Ctrl/Cmd untuk memilih lebih dari satu.</div>
          @error('roles')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="mt-4">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-light">Batal</a>
      </div>
    </form>
  </div>
</div>
@endsection
