@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <h4 class="mb-3">{{ $title }}</h4>

  @if (empty($rows))
    <div class="alert alert-warning">Tidak ada baris data yang terbaca. Pastikan baris pertama adalah header dan ada minimal satu baris berisi data.</div>
  @else
    <div class="mb-2 text-muted">
      Menampilkan {{ count($rows) }} baris data dari file.
    </div>

    <div class="card mb-3">
      <div class="card-header">Preview Data</div>
      <div class="card-body table-responsive">
        <table id="preview-table" class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              @foreach($headers as $h)
                <th>{{ $h }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($rows as $r)
              <tr>
                @foreach($headers as $h)
                  @php $val = $r[$h] ?? null; @endphp
                  <td>{{ is_null($val) ? '' : $val }}</td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <form method="POST" action="{{ route('siswa.import.commit') }}">
      @csrf
      <input type="hidden" name="tempPath" value="{{ $tempPath }}">
      <button type="submit" class="btn btn-success">Commit Import</button>
      <a href="{{ route('siswa.import') }}" class="btn btn-outline-secondary">Kembali</a>
    </form>
  @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.jQuery && jQuery.fn.DataTable) {
    jQuery('#preview-table').DataTable({
      paging: true,
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      ordering: true,
      searching: true,
      responsive: true,
      scrollX: true,      // horizontal scroll
      scrollY: '60vh',    // tinggi table, aktifkan freeze header
      scrollCollapse: true,
      fixedHeader: true,  // freeze header
      autoWidth: false,
    });
  }
});
</script>
@endpush