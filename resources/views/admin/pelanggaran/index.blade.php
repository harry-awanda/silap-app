@extends('layouts.app')
@section('content')
@include('layouts.toasts')
<!-- Content -->

<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <!-- <div class="ms-auto"> -->
      <div class="btn-group" role="group">
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#importModal">
          <i class="bx bx-import me-2"></i>Import Data</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDataPelanggaranModal">
          <i class="bx bx-plus me-2"></i>Tambah Data
        </button>
      <!-- </div> -->
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Jenis Pelanggaran</th>
            <th>Nama Pelanggaran</th>
            <th class="text-center">Pilihan</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($pelanggaran as $data)
          <tr>
            <td class="text-center" width="80px">{{ $loop->index+1 }}</td>
            <td>{{ $data->jenis }}</td>
            <td>{{ $data->nama }}</td>
            <td class="text-center" width="200px">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#editDataPelanggaranModal{{ $data->id }}">
                    <i class="bx bx-edit-alt me-1"></i> Edit
                  </a>
                  <form action="{{ route('admin.pelanggaran.destroy', $data->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item" onclick="return confirm('Apakah Anda yakin ingin menghapus data pelanggaran ini?')">
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

<!-- Modal tambah data pelanggaran -->
<div class="modal modal-top fade" id="createDataPelanggaranModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Data Pelanggaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.pelanggaran.store') }}" method="POST">
        <div class="modal-body">
          @csrf
          <div class="row">
            <div class="mb-3">
              <label for="jenis_pelanggaran" class="form-label">Jenis Pelanggaran</label>
              <input type="text" class="form-control" name="jenis" required>
            </div>
            <div class="mb-3">
              <label for="nama_pelanggaran" class="form-label">Nama Pelanggaran</label>
              <input type="text" class="form-control" name="nama" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
              <i class="bx bx-x me-2"></i>Close
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-2"></i>Simpan
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal edit data pelanggaran -->
@foreach ($pelanggaran as $data)
<div class="modal modal-top fade" id="editDataPelanggaranModal{{ $data->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Data Pelanggaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('admin.pelanggaran.update', $data->id) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="row">
            <div class="mb-3">
              <label for="jenis_pelanggaran" class="form-label">Jenis Pelanggaran</label>
              <input type="text" class="form-control" name="jenis" value="{{ $data->jenis }}" required>
            </div>
            <div class="mb-3">
              <label for="nama_pelanggaran" class="form-label">Nama Pelanggaran</label>
              <input type="text" class="form-control" name="nama" value="{{ $data->nama }}" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
              <i class="bx bx-x me-2"></i>Close
            </button>
            <button type="submit" class="btn btn-warning">
              <i class="bx bx-save me-2"></i>Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach
<!-- Modal import jenis pelanggaran -->
<div class="modal modal-top fade" id="importModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalTitle">Import Data Pelanggaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('admin.pelanggaran.import') }}" method="POST" enctype="multipart/form-data">
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
              <i class="bx bx-import me-2"></i>Import
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