@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.roles.index') }}">Roles</a> /
  <span class="text-muted fw-light">Tambah</span>
</h4>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.roles.store') }}">
      @csrf

      <div class="mb-3">
        <label class="form-label">Nama Role</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        <div class="form-text">Contoh: admin, guru, guru_bk, guru_piket, kesiswaan, siswa</div>
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      {{-- Guard diset tetap web (hidden) --}}
      <input type="hidden" name="guard_name" value="web">

      <button class="btn btn-primary">Simpan</button>
      <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Batal</a>
    </form>
  </div>
</div>
@endsection
