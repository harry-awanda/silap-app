@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
  @if($activeTerm)
    <span class="badge bg-label-info ms-2">
      {{ $activeTerm->year_start }}/{{ $activeTerm->year_end }} -
      Semester {{ ucfirst($activeTerm->semester) }}
    </span>
  @endif
</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Daftar Jadwal Piket</h5>
    <a href="{{ route('admin.jadwal-piket.create') }}" class="btn btn-primary">
      <i class="bx bx-plus me-1"></i> Tambah Data
    </a>
  </div>

  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover">
      <table class="table datatable">
        <thead class="table-light">
          <tr>
            <th class="text-center" width="50">#</th>
            <th>Nama Guru</th>
            <th>Hari Piket</th>
            <th class="text-center" width="150">Aksi</th>
          </tr>
        </thead>

        <tbody>
          @foreach ($jadwalPiket as $data)
          <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td>{{ $data->guru->nama_lengkap ?? '-' }}</td>
            <td>{{ $data->hari_piket }}</td>
            <td class="text-center">
              <div class="dropdown">
                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="{{ route('admin.jadwal-piket.edit', $data->id) }}">
                    <i class="bx bx-edit-alt me-1"></i> Edit
                  </a>
                  <form action="{{ route('admin.jadwal-piket.destroy', $data->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
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
  // Inisialisasi DataTables
  new DataTable('.datatable', {
    paging: true,
    searching: true,
    info: true,
    order: [[2, 'asc']], // urut berdasarkan hari piket
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
    }
  });
</script>
@endpush
