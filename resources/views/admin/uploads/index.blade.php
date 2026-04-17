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
      <button class="btn btn-primary" data-bs-toggle="modal"
        data-bs-target="#createUploadModal">
        <i class="bx bx-upload me-2"></i>Upload 
      </button>
    <!-- </div> -->
  </div>
  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Nama File</th>
            <th>Keterangan</th>
            <th>Tipe File</th>
            <th class="text-center">Pilihan</th>
          </tr>
        </thead>
        <tbody>
          @foreach($uploads as $upload)
          <tr>
            <td class="text-center" width="80px">{{ $loop->index+1 }}</td>
            <td>{{ $upload->file_name }}</td>
            <td>{{ $upload->description }}</td>
            <td>{{ strtoupper($upload->file_type) }}</td>
            <td class="text-center" width="200px">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="{{ route('admin.uploads.download', $upload->id) }}">
                    <i class="bx bx-download me-2"></i> Download
                  </a>
                  <form action="{{ route('admin.uploads.destroy', $upload->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item" onclick="return confirm('Apakah Anda yakin ingin menghapus ini?')">
                      <i class="bx bx-trash me-2"></i> Delete
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
<!-- Modal to add new record -->
<div class="modal modal-top fade" id="createUploadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Unggah Berkas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('admin.uploads.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="row">
            <div class="form-group">
              <label for="file">File</label>
              <input type="file" class="form-control" id="file" name="file" required>
            </div>
            <div class="form-group">
              <label for="description">Keterangan</label>
              <textarea class="form-control" id="description" name="description"></textarea>
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
<!-- / Content -->

@endsection
@push ('scripts')
  <script>
    let table = new DataTable('.datatable');
  </script>
@endpush