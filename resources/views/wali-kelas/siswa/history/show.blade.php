@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('wali.siswa-history.index', ['assignment_id' => $assignment->id]) }}">Riwayat Siswa Binaan</a> /
  <span class="text-muted fw-light">Detail</span>
</h4>

<div class="card mb-3">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="fw-semibold text-uppercase">{{ $siswa->nama_lengkap }}</div>
    <span class="badge bg-label-secondary">Read-only</span>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-12 col-md-4">
        <div class="text-muted small">NIS</div>
        <div class="fw-semibold">{{ $siswa->nis }}</div>
      </div>
      <div class="col-12 col-md-4">
        <div class="text-muted small">Jenis Kelamin</div>
        <div class="fw-semibold">{{ $siswa->jenis_kelamin }}</div>
      </div>
      <div class="col-12 col-md-4">
        <div class="text-muted small">Kelas / Term</div>
        <div class="fw-semibold">
          {{ $assignment->classroom?->tingkat }} {{ $assignment->classroom?->nama_kelas }}
          -
          {{ $assignment->term?->year_start }}/{{ $assignment->term?->year_end }} {{ ucfirst($assignment->term?->semester) }}
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-7">
    <div class="card h-100">
      <div class="card-header fw-semibold">Riwayat Presensi Terakhir</div>
      <div class="card-body">
        @if($attendances->isEmpty())
          <div class="text-muted">Belum ada data presensi pada term ini.</div>
        @else
          <div class="table-responsive text-nowrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Waktu</th>
                  <th>Status</th>
                  <th>Sumber</th>
                </tr>
              </thead>
              <tbody>
                @foreach($attendances as $row)
                  <tr>
                    <td>{{ optional($row->date)->format('d/m/Y') }}</td>
                    <td>{{ $row->time }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                    <td>{{ $row->source }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card h-100">
      <div class="card-header fw-semibold">Riwayat Pelanggaran</div>
      <div class="card-body">
        @if($violations->isEmpty())
          <div class="text-muted">Belum ada data pelanggaran pada term ini.</div>
        @else
          <div class="table-responsive text-nowrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Status</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                @foreach($violations as $row)
                  <tr>
                    <td>{{ $row->tanggal_pelanggaran ? \Carbon\Carbon::parse($row->tanggal_pelanggaran)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $row->keterangan }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
