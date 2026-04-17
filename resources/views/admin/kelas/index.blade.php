@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

<div class="card">
<div class="card-header d-flex justify-content-start align-items-center">
    <div class="btn-group">
      <a href="{{ route('admin.classrooms.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-2"></i>Tambah Data
      </a>

      <a href="{{ route('admin.classrooms.clone.form') }}" class="btn btn-label-secondary">
        <i class="bx bx-copy me-2"></i>Clone Struktur Kelas
      </a>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Nama Kelas</th>
            <th>Wali Kelas (Aktif)</th>
            <th>Tingkat</th>
            <th>Total Siswa</th>
            <th class="text-center">Pilihan</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($classrooms as $data)
          <tr>
            <td class="text-center" width="80">{{ $loop->iteration }}</td>
            <td>{{ $data->nama_kelas }}</td>
            <td>
              @if($data->currentWaliGuru)
                {{ $data->currentWaliGuru->nama_lengkap }}
              @else
                <span class="badge bg-label-warning">Belum ditetapkan</span>
              @endif
            </td>
            <td>{{ $data->tingkat }}</td>
            <td>{{ $data->siswa_count }}</td>
            <td class="text-center" width="200">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="{{ route('admin.classrooms.edit', $data->id) }}">
                    <i class="bx bx-edit-alt me-2"></i> Edit
                  </a>
                  <form action="{{ route('admin.classrooms.destroy', $data->id) }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="dropdown-item"
                      onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                      <i class="bx bx-trash me-1"></i> Hapus
                    </button>
                  </form>
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
<script>
  let table = new DataTable('.datatable');
</script>
@endpush
