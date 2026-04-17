@extends('layouts.app')
@section('content')
@include('layouts.toasts')
<!-- Content -->
<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> / 
  <span class="text-muted fw-light"> {{ $title }}</span>
</h4>
@if($errors->any())
<div class="alert alert-danger">
  <ul>
    @foreach($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <!-- <div class="ms-auto"> -->
      <div class="btn-group" role="group">
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#importModal">
          <i class="bx bx-import me-2"></i>Import Data
        </button>
        <a href="{{ route('admin.guru.create') }}" class="btn btn-primary">
          <i class="bx bx-plus me-2"></i>Tambah Data
        </a>
      </div>
    <!-- </div> -->
  </div>
  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>NIP / NRPTK / NRHS</th>
            <th>Nama Guru</th>
            <th>L / P</th>
            <th class="text-center">Pilihan</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($guru as $data)
          <tr>
            <td class="text-center" width="80px">{{ $loop->index+1 }}</td>
            <td>{{ $data->nip }}</td>
            <td>{{ $data->nama_lengkap }}</td>
            <td>{{ $data->jenis_kelamin }}</td>
            <td class="text-center" width="200px">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="{{ route('admin.guru.edit', $data->id) }}">
                    <i class="bx bx-edit-alt me-2"></i> Edit
                  </a>
                  <form action="{{ route('admin.guru.destroy', $data->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                      <i class="bx bx-trash me-2"></i> Hapus
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
<!-- / Content -->
<!-- Modal to add new record -->
<div class="modal modal-top fade" id="importModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Data Guru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('admin.guru.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="row">
            <div class="col-md-12">
              <label class="form-label" for="file">Pilih Berkas Excel</label>
              <input type="file" name="file" class="form-control" required>
            </div>
          </div>
          <hr>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
              <i class="bx bx-x me-2"></i>Close
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-2"></i> Upload
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
@push ('scripts')
  <script>
    let table = new DataTable('.datatable');
  </script>
@endpush