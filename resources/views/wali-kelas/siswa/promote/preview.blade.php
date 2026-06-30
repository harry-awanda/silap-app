@extends('layouts.app')

@section('content')
@include('layouts.toasts')

@php
  $kind = $payload['promote_kind'] ?? 'advance';
  $kindLabel = $kind === 'repeat' ? 'Tinggal Kelas' : 'Naik Kelas';

  // untuk tombol kembali agar state tidak reset
  $backParams = [
    'classroom_id' => $payload['classroom_id'] ?? null,
  ];
  if ($mode === 'promote') {
    $backParams += [
      'to_term_id'     => $payload['to_term_id'] ?? null,
      'promote_kind'   => $kind,
      'target_classid' => $payload['target_classid'] ?? null,
    ];
  }
  $indexRoute = 'admin.siswa.promosi.index';
  $commitRoute = 'admin.siswa.promosi.commit';
@endphp

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route($indexRoute, [$mode] + $backParams) }}">
    {{ $mode === 'promote' ? 'Naik Kelas' : 'Kelulusan' }}
  </a> /
  <span class="text-muted fw-light">Preview</span>
</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="fw-semibold">
      Konfirmasi {{ $mode === 'promote' ? 'Promosi Siswa' : 'Kelulusan' }}
    </div>

    <div class="d-flex gap-2 flex-wrap justify-content-end">
      <span class="badge bg-label-secondary">Term Sumber: {{ $fromTermLabel }}</span>

      @if($mode === 'promote')
        <span class="badge bg-label-primary">Term Tujuan: {{ $toTermLabel }}</span>
        <span class="badge bg-label-warning">Jenis: {{ $kindLabel }}</span>
        <span class="badge bg-label-primary">
          Kelas Tujuan: {{ $targetClass?->tingkat }} - {{ $targetClass?->nama_kelas }}
        </span>
      @else
        <span class="badge bg-label-primary">Angkatan: {{ $payload['angkatan'] }}</span>
      @endif
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover mb-3">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>JK</th>
          </tr>
        </thead>
        <tbody>
          @foreach($siswa as $row)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ $row->nis }}</td>
              <td class="text-uppercase">{{ $row->nama_lengkap }}</td>
              <td>{{ $row->jenis_kelamin }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <form action="{{ route($commitRoute, $mode) }}" method="POST" class="d-flex flex-column gap-2">
      @csrf

      <input type="hidden" name="payload[classroom_id]" value="{{ $payload['classroom_id'] }}">

      @foreach($payload['siswa_ids'] as $id)
        <input type="hidden" name="payload[siswa_ids][]" value="{{ $id }}">
      @endforeach

      @if($mode === 'promote')
        <input type="hidden" name="payload[from_term_id]" value="{{ $payload['from_term_id'] }}">
        <input type="hidden" name="payload[to_term_id]" value="{{ $payload['to_term_id'] }}">
        <input type="hidden" name="payload[target_classid]" value="{{ $payload['target_classid'] }}">
        <input type="hidden" name="payload[promote_kind]" value="{{ $payload['promote_kind'] ?? 'advance' }}"> {{-- ✅ baru --}}
        <label class="form-label">Ketik <b>PROMOTE</b> untuk konfirmasi</label>
      @else
        <input type="hidden" name="payload[angkatan]" value="{{ $payload['angkatan'] }}">
        <label class="form-label">Ketik <b>LULUS</b> untuk konfirmasi</label>
      @endif

      <input type="text" name="confirm" class="form-control" required>

      <div class="d-flex justify-content-end gap-2">
        <a href="{{ route($indexRoute, [$mode] + $backParams) }}" class="btn btn-label-secondary">
          Kembali
        </a>
        <button class="btn btn-success">Proses</button>
      </div>
    </form>
  </div>
</div>
@endsection
