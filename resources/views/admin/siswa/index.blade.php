@extends('layouts.app')
@section('content')
@include('layouts.toasts')
<!-- Content -->
<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> / 
  <span class="text-muted fw-light"> {{ $title }}</span>
</h4>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <!-- <div class="ms-auto"> -->
      <a href="{{ route('admin.siswa.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-2"></i>Tambah Data
      </a>
    <!-- </div> -->
  </div>
  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover">
      <table class="table siswa-table">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>L/P</th>
            <th>Nama Kelas</th>
            <th class="text-center">Pilihan</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
  <!-- / Content -->
  @include('admin.siswa.partials.actions')

@endsection
@push ('scripts')
<script>
  $(function () {
    const tpl = document.getElementById('tpl-actions-siswa')?.innerHTML ?? '';
    
    const table = $('.siswa-table').DataTable({
      processing: false,
      serverSide: true,
      ajax: "{{ route('admin.siswa.data') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'nis', name: 'nis' },
        { data: 'nama_lengkap', name: 'nama_lengkap' },
        { data: 'jenis_kelamin', name: 'jenis_kelamin' },
        { data: 'kelas', name: 'kelas' },
        {
          data: 'id',
          name: 'pilihan',
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            if (!tpl) return '';
            // Ganti semua :id dengan id siswa
            return tpl.replaceAll(':id', data);
          }
        },
      ],
      columnDefs: [
        // Kolom terakhir (Pilihan) rata tengah dan set lebar ±200px
        { targets: -1, className: 'text-center', width: '200px' },
        // Kolom pertama (#) juga rata tengah
        { targets: 0, className: 'text-center', width: '50px' },
      ]
    });
  });
</script>
@endpush