@extends('layouts.app')

@section('content')
@include('layouts.toasts')

  <h4 class="py-3 mb-4">
    <a href="{{ route('dashboard') }}">Dashboard</a> /
    <span class="text-muted fw-light">{{ $title }}</span>
  </h4>

  {{-- Card Filter (dipisah) --}}
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('absence.index') }}"
        class="row g-2 align-items-end">

        <div class="col-12 col-md-4">
          <label class="form-label mb-1">Tanggal</label>
          <input type="date" name="date" class="form-control"
            value="{{ $date }}" max="{{ now()->toDateString() }}">
        </div>

        <div class="col-12 col-md-auto d-flex gap-2">
          <button class="btn btn-outline-primary" type="submit">
            <i class="bx bx-filter-alt me-1"></i> Tampilkan
          </button>

          <a href="{{ route('absence.create', ['date' => $date]) }}" class="btn btn-primary">
            <i class="bx bx-plus me-2"></i> Tambah / Edit
          </a>
        </div>
      </form>
    </div>
  </div>

  {{-- Card Tabel --}}
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Daftar Absensi - {{ $classroom->nama_kelas }}</h5>
      {{-- <small class="text-muted">Tanggal: {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }}</small> --}}
    </div>

    <div class="card-body">
      @if($absences->isNotEmpty())
        <div class="table-responsive text-nowrap">
          <table class="table table-hover align-middle datatable w-100">
            <thead>
              <tr>
                <th style="width:60px;">#</th>
                <th>Nama Siswa</th>
                <th class="text-nowrap">NIS</th>
                <th>Status</th>
                <th style="width:80px;">Pilihan</th>
              </tr>
            </thead>
            <tbody>
              @foreach($absences as $absence)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td class="text-wrap">{{ $absence->siswa->nama_lengkap }}</td>
                  <td class="text-nowrap">{{ $absence->siswa->nis }}</td>
                  <td>
                    <span class="badge
                      @switch($absence->status)
                        @case('sakit') bg-label-warning @break
                        @case('izin')  bg-label-info    @break
                        @case('alpa')  bg-label-danger  @break
                        @default       bg-label-secondary
                      @endswitch
                    ">{{ ucfirst($absence->status) }}</span>
                  </td>
                  <td>
                    <form action="{{ route('absence.destroy', ['attendance' => $absence, 'date' => $date]) }}"
                          method="POST" onsubmit="return confirm('Hapus data ini?')" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-text text-danger">
                        <i class="bx bx-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="alert alert-info mb-0">
          Belum ada data ketidakhadiran pada tanggal ini.
        </div>
      @endif
    </div>
  </div>
@endsection

@push('scripts')
<script>
  if (typeof DataTable === 'function') {
    let table = new DataTable('.datatable');
  }
</script>
@endpush
