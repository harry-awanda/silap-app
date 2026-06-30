@extends('layouts.app')

@section('content')
@include('layouts.toasts')

@php
  $selectedAssignmentId = request('assignment_id', $assignment?->id);
@endphp

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

<div class="card mb-3">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="fw-semibold">Riwayat Kelas Binaan</div>
    <span class="badge bg-label-secondary">Read-only</span>
  </div>
  <div class="card-body">
    @if($assignments->isEmpty())
      <div class="text-muted">Belum ada riwayat penugasan wali kelas untuk akun ini.</div>
    @else
      <form method="GET" action="{{ route('wali.siswa-history.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-8">
          <label class="form-label">Term dan Kelas</label>
          <select class="form-select" name="assignment_id" onchange="this.form.submit()">
            @foreach($assignments as $item)
              <option value="{{ $item->id }}" @selected((string)$selectedAssignmentId === (string)$item->id)>
                {{ $item->term?->year_start }}/{{ $item->term?->year_end }}
                - {{ ucfirst($item->term?->semester) }}
                - {{ $item->classroom?->tingkat }} {{ $item->classroom?->nama_kelas }}
                {{ $item->ended_at ? '(selesai)' : '(aktif)' }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4 d-grid">
          <button class="btn btn-label-primary" type="submit">Tampilkan</button>
        </div>
      </form>
    @endif
  </div>
</div>

@if($assignment)
  <div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="fw-semibold">
        {{ $assignment->classroom?->tingkat }} {{ $assignment->classroom?->nama_kelas }}
      </div>
      <span class="badge bg-label-primary">
        {{ $assignment->term?->year_start }}/{{ $assignment->term?->year_end }} - {{ ucfirst($assignment->term?->semester) }}
      </span>
    </div>
    <div class="card-body">
      @if($siswa->isEmpty())
        <div class="text-muted">Tidak ada siswa pada riwayat kelas ini.</div>
      @else
        <div class="table-responsive text-nowrap table-hover">
          <table class="table datatable">
            <thead>
              <tr>
                <th style="width:60px">#</th>
                <th>NIS</th>
                <th>Nama</th>
                <th>JK</th>
                <th>Status</th>
                <th style="width:90px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($siswa as $row)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $row->nis }}</td>
                  <td class="text-uppercase">{{ $row->nama_lengkap }}</td>
                  <td>{{ $row->jenis_kelamin }}</td>
                  <td>
                    <span class="badge {{ $row->placement_status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                      {{ ucfirst($row->placement_status) }}
                    </span>
                  </td>
                  <td>
                    <a
                      href="{{ route('wali.siswa-history.show', [$assignment->id, $row->id]) }}"
                      class="btn btn-sm btn-icon btn-outline-secondary"
                      title="Detail"
                    >
                      <i class="bx bx-show"></i>
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (document.querySelector('.datatable')) {
    new DataTable('.datatable', {
      perPage: 25,
      order: [[2, 'asc']]
    });
  }
});
</script>
@endpush
