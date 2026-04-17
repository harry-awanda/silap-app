@extends('layouts.app')

@section('content')
@include('layouts.toasts')

@php
  $u = auth()->user();

  // Formatter angka dan persentase
  $formatNum = fn($n) => number_format((int) $n, 0, ',', '.');
  $formatPct = fn($p) => is_null($p)
    ? '0%'
    : (rtrim(rtrim(number_format((float)$p, 1, ',', '.'), '0'), ',') . '%');

  // Warna status kehadiran (untuk badge / chart)
  $statusColor = [
    'hadir'     => 'success',
    'terlambat' => 'warning',
    'izin'      => 'info',
    'sakit'     => 'primary',
    'alpa'      => 'danger',
    'belum'     => 'secondary',
  ];
@endphp

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">SILAP</a>
  <span class="text-muted fw-light"> / </span>
  <span class="text-muted fw-light">{{ $title ?? 'Ringkasan' }}</span>
</h4>

@if(!$u->hasAnyRole(['admin','guru','kesiswaan','guru_piket','guru_bk','siswa']))
  <div class="alert alert-danger">Anda tidak memiliki akses ke Dashboard ini.</div>
@endif

{{-- HEADER --}}
@include('dashboard.partials.header-summary')

{{-- KARTU STATISTIK UTAMA --}}
@include('dashboard.partials.statistik-utama')

@if(empty($isSiswa) || !$isSiswa)
{{-- AKTIVITAS TERBARU --}}
@include('dashboard.partials.aktivitas-terbaru')

<div class="row">
  <div class="col-12 col-lg-5 mb-4">
    @include('dashboard.partials.rekap-status')
  </div>

  <div class="col-12 col-lg-7 mb-4">
    @include('dashboard.partials.top-terlambat')
  </div>

  <div class="col-12">
    @include('dashboard.partials.persentase-absen')
  </div>
</div>
@endif

@endsection
