@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">Penugasan Wali Kelas</span>
</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.homeroom.create') }}" class="btn btn-primary">
      <i class="bx bx-plus me-2"></i>Tambah Penugasan
    </a>
  </div>
  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th>#</th>
            <th>Kelas</th>
            <th>Wali Kelas</th>
            <th>Mulai</th>
            <th>Selesai</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
        @foreach($assignments as $a)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $a->classroom->nama_kelas }}</td>
            <td>{{ $a->guru->nama_lengkap }}</td>
            <td>{{ optional($a->started_at)->format('d M Y H:i') }}</td>
            <td>{{ optional($a->ended_at)->format('d M Y H:i') ?: '—' }}</td>
            <td>
              @if(is_null($a->ended_at))
                <span class="badge bg-label-success">Aktif</span>
              @else
                <span class="badge bg-label-secondary">Riwayat</span>
              @endif
            </td>
            <td class="text-center">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
            
                <div class="dropdown-menu">
                  {{-- Edit --}}
                  <a class="dropdown-item"
                    href="{{ route('admin.homeroom.edit', $a->id) }}">
                    <i class="bx bx-edit-alt me-2 text-info"></i>Edit
                  </a>
                  @if(is_null($a->ended_at))
                    {{-- Akhiri --}}
                    <form method="POST"
                      action="{{ route('admin.homeroom.end', $a->id) }}"
                      onsubmit="return confirm('Akhiri penugasan ini?')">
                      @csrf
                        <button type="submit" class="dropdown-item text-warning">
                          <i class="bx bx-stop-circle me-2"></i>Akhiri
                        </button>
                    </form>
                  @else
                  {{-- Hapus riwayat --}}
                    <form method="POST"
                      action="{{ route('admin.homeroom.destroy', $a->id) }}"
                      onsubmit="return confirm('Hapus riwayat penugasan ini?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger">
                        <i class="bx bx-trash me-2"></i>Hapus
                      </button>
                    </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script> new DataTable('.datatable'); </script>
@endpush
