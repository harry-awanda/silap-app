@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

{{-- Tabs --}}
<ul class="nav nav-pills flex-column flex-md-row mb-4">
  <li class="nav-item">
    <a class="nav-link" href="{{ route('siswa.edit', $siswa->id) }}">
      <i class="bx bx-user me-1"></i> Profil
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="{{ route('siswa.pelanggaran.index', $siswa->id) }}">
      <i class="bx bx-shield me-1"></i> Riwayat Pelanggaran
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link active" href="{{ route('siswa.absensi.index', $siswa->id) }}">
      <i class="bx bx-calendar-x me-1"></i> Riwayat Absensi
    </a>
  </li>
</ul>

<div class="card">

  {{-- Header: Filter tanggal --}}
  <div class="card-header d-flex justify-content-start align-items-center">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center w-100">
      <div>
        <input type="date" name="from" value="{{ $from }}" class="form-control" />
      </div>
      <div>
        <input type="date" name="to" value="{{ $to }}" class="form-control" />
      </div>
      <div class="btn-group">
        <button class="btn btn-primary" type="submit">
          <i class="bx bx-filter-alt me-2"></i>Filter
        </button>
        <a href="{{ route('siswa.absensi.index', $siswa->id) }}" class="btn btn-label-secondary">
          <i class="bx bx-reset me-2"></i>Reset
        </a>
      </div>

      {{-- info siswa (opsional, tapi membantu) --}}
      <div class="ms-auto">
        <span class="badge bg-label-primary">
          {{ $siswa->nis }} - {{ $siswa->nama_lengkap }}
        </span>
      </div>
    </form>
  </div>

  <div class="card-body">

    {{-- Rekap singkat --}}
    <div class="row g-2 mb-3">
      <div class="col-6 col-md">
        <div class="p-2 border rounded">
          <div class="text-muted small">Izin</div>
          <div class="fw-semibold">{{ $rekap['izin'] ?? 0 }}</div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="p-2 border rounded">
          <div class="text-muted small">Sakit</div>
          <div class="fw-semibold">{{ $rekap['sakit'] ?? 0 }}</div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="p-2 border rounded">
          <div class="text-muted small">Alpa</div>
          <div class="fw-semibold">{{ $rekap['alpa'] ?? 0 }}</div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="p-2 border rounded">
          <div class="text-muted small">Terlambat</div>
          <div class="fw-semibold">{{ $rekap['terlambat'] ?? 0 }}</div>
        </div>
      </div>
    </div>

    {{-- Tabel riwayat --}}
    <div class="table-responsive text-nowrap table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Tanggal</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $row)
            @php
              $status = strtolower($row->status ?? '');
              $badge = match($status) {
                'izin'      => 'info',
                'sakit'     => 'warning',
                'alpa'     => 'danger',
                'terlambat' => 'secondary',
                default     => 'dark'
              };
            @endphp
            <tr>
              <td class="text-center" width="80">{{ $loop->iteration }}</td>
              <td>{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td>
              <td>
                <span class="badge bg-label-{{ $badge }}">
                  {{ ucfirst($status ?: '-') }}
                </span>
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
  $('.datatable').DataTable({
    language: {
      emptyTable: "Belum ada data absensi siswa.",
      zeroRecords: "Tidak ada data absensi pada rentang tanggal tersebut.",
    }
  });
</script>
@endpush