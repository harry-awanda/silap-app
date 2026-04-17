@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
      <i class="bx bx-plus me-2"></i>Tambah Role
    </a>
  </div>

  <div class="card-body">
    <div class="table-responsive table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th style="width:70px" class="text-center">#</th>
            <th>Nama Role</th>
            <th class="text-center" style="width:140px">Dipakai</th>
            <th class="text-center" style="width:200px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($roles as $i => $r)
            <tr>
              <td class="text-center">{{ $i+1 }}</td>
              <td><span class="badge bg-label-primary">{{ $r->name }}</span></td>
              <td class="text-center">{{ $r->users_count ?? 0 }}</td>
              <td class="text-center">
                <a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-warning">Ubah</a>
                <form action="{{ route('admin.roles.destroy', $r) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Hapus role ini?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-danger" {{ $r->name==='admin' ? 'disabled' : '' }}>
                    Hapus
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted">Belum ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push ('scripts')
  <script>
    let table = new DataTable('.datatable');
  </script>
@endpush
