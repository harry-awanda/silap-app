@extends('layouts.app')

@php
  $u = auth()->user();

  // Formatter angka dan persentase
  $formatNum = fn($n) => number_format((int) $n, 0, ',', '.');
  $formatPct = fn($p) => is_null($p)
    ? '0%'
    : (rtrim(rtrim(number_format((float)$p, 1, ',', '.'), '0'), ',') . '%');

@endphp

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Dashboard /</span>
  <span>Siswa</span>
</h4>

<div class="card mb-4">
  <div class="card-body text-center">
    <h5 class="mb-2">Selamat datang, {{ auth()->user()->name }}</h5>
    <p class="text-muted mb-0">
      Tanggal:
      <strong>{{ $formattedDate }}</strong>.
    </p>
  </div>
</div>

{{-- ========== STATISTIK UTAMA ========== --}}
@include('dashboard.partials.statistik-utama')


@endsection
