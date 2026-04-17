@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.roles.index') }}">Roles</a> /
  <span class="text-muted fw-light">Ubah</span>
</h4>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
      @csrf @method('PUT')

      <div class="mb-3">
        <label class="form-label">Nama Role</label>
        <input type="text" name="name" class="form-control" value="{{ old('name',$role->name) }}" required>
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <button class="btn btn-primary">Simpan</button>
      <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Batal</a>
    </form>
  </div>
</div>
@endsection
