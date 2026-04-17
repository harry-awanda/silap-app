@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('admin.users.index') }}">Manajemen User</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nama Lengkap</label>
          <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Username</label>
          <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Role</label>
          <select name="roles[]" id="roles" class="form-select select2" multiple>
            @foreach ($allRoles as $role)
              <option value="{{ $role->name }}"
                {{ in_array($role->name, $userRoles) ? 'selected' : '' }}>
                {{ ucfirst($role->name) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Password Baru (Opsional)</label>
          <input type="password" name="password" class="form-control">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Konfirmasi Password</label>
          <input type="password" name="password_confirmation" class="form-control">
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Kembali</a>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    $('#roles').select2({
      placeholder: 'Pilih satu atau lebih role',
      width: '100%'
    });
  });
</script>
@endpush
