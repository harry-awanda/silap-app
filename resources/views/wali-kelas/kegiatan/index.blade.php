@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">Absensi Kegiatan Pagi</span>
</h4>

{{-- =========================
  CARD FILTER (Tanggal + Kegiatan)
========================= --}}
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" action="{{ route('kegiatan-absensi.index') }}" class="row g-2 align-items-end">
      <div class="col-12 col-md-3">
        <label class="form-label">Tanggal</label>
        <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}">
      </div>

      <div class="col-12 col-md-4">
        <label class="form-label">Kegiatan</label>
        <select name="activity_id" class="form-select">
          <option value="">Semua kegiatan</option>
          @foreach ($activities as $act)
            <option value="{{ $act->id }}"
              @selected((int)($selectedActivityId ?? 0) === (int)$act->id)>
              {{ $act->nama }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-12 col-md-auto">
        <button class="btn btn-outline-primary">
          <i class="bx bx-filter-alt me-2"></i>Terapkan
        </button>
      </div>
    </form>
  </div>
</div>

{{-- =========================
  CARD REKAP + TABEL
========================= --}}
<div class="card">
  <div class="card-body">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h4 class="mb-1">Kelas {{ $classroom->nama_kelas }}</h4>
        <small class="text-muted">
          Tanggal: {{ \Illuminate\Support\Carbon::parse($tanggal)->format('d-m-Y') }}
        </small>

        @if(!empty($headerActivityName))
          <div class="mt-2">
            <span class="badge bg-label-primary">Kegiatan: {{ $headerActivityName }}</span>
          </div>
        @endif
      </div>

      <div>
        <a class="btn btn-primary"
          href="{{ route('kegiatan-absensi.create', ['tanggal' => $tanggal, 'activity_id' => $selectedActivityId]) }}">
          <i class="bx bx-plus me-2"></i>Input/Ubah Absensi
        </a>
      </div>
    </div>

    <div class="mb-3">
      <span class="badge bg-label-success">Hadir: {{ $hadir }}</span>
      <span class="badge bg-label-danger ms-2">Tidak Hadir: {{ $tidak }}</span>
    </div>

    @if (($hadir + $tidak) === 0)
      <div class="alert alert-warning mb-0">
        Belum ada data absensi untuk tanggal ini.
        Klik <strong>Input/Ubah Absensi</strong> untuk mengisi.
      </div>
    @else
      <div class="table-responsive text-nowrap table-hover">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th style="width:60px">#</th>
              <th style="width:120px">NIS</th>
              <th>Nama</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            @php $i = 1; @endphp
            @forelse ($absents as $row)
              <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $row->siswa->nis ?? '-' }}</td>
                <td>{{ $row->siswa->nama_lengkap ?? '-' }}</td>
                <td>{{ $row->keterangan ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-success">Semua siswa hadir. 🎉</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif

  </div>
</div>
@endsection