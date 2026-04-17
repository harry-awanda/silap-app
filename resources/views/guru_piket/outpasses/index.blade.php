@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">Izin Keluar</span>
</h4>

<div class="d-flex justify-content-between mb-3">
  <div class="d-flex gap-2">
    <input type="date" id="filterDate" class="form-control" style="max-width: 180px;">
    <select id="filterStatus" class="form-select" style="max-width: 180px;">
      <option value="">Semua Status</option>
      <option value="aktif">Sedang di Luar</option>
      <option value="kembali">Sudah Kembali</option>
      <option value="pulang">Tidak Kembali</option>
    </select>
  </div>
  <a href="{{ route('guru-piket.outpasses.create') }}" class="btn btn-primary">Buat Izin Baru</a>
</div>

<div class="card">
  <div class="card-body">
    <table id="dt" class="table table-striped w-100">
      <thead>
        <tr>
          <th>#</th>
          <th>Nama</th>
          <th>Kelas</th>
          <th>Tujuan</th>
          <th>Jam Keluar</th>
          <th>Jam Kembali</th>
          <th>Guru Piket</th>
          <th>Aksi</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@push('scripts')
<script>
$(function(){
  const tbl = $('#dt').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "{{ route('guru-piket.outpasses.dt') }}",
      data: function(d){
        d.date = $('#filterDate').val() || '';
        d.status = $('#filterStatus').val() || '';
      }
    },
    order: [[4,'desc']],
    columns: [
      { data: null, orderable:false, searchable:false,
        render: (d,t,r,meta)=> meta.row + meta.settings._iDisplayStart + 1 },
      { data: 'siswa', name:'siswa' },
      { data: 'kelas', name:'kelas' },
      { data: 'tujuan', name:'tujuan' },
      { data: 'jam_keluar', name:'jam_keluar' },
      { data: 'jam_kembali', name:'jam_kembali' },
      { data: 'guru_piket', name:'guru_piket' },
      { data: 'aksi', orderable:false, searchable:false }
    ]
  });

  $('#filterDate,#filterStatus').on('change', ()=> tbl.ajax.reload());

  $(document).on('click','.js-return-now', async function(){
    const id = this.dataset.id;
    const resp = await fetch(`{{ url('guru-piket/izin/keluar/detail') }}/${id}/return`,{
      method:'POST',
      headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}
    });
    if(resp.ok) { tbl.ajax.reload(null,false); } else { alert('Gagal menandai kembali'); }
  });
});
</script>
@endpush
@endsection