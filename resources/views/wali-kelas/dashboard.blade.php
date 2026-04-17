@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4"><span>Dashboard Wali Kelas</span></h4>

<div class="card mb-4">
  <div class="card-body d-flex align-items-center justify-content-between">
    <div>
      <h5 class="mb-1">{{ $namaKelasWali }}</h5>
      <small class="text-muted">Tanggal: {{ $formattedDate }}</small>
    </div>
    <div>
      <a href="{{ route('wali.audit.attendance.index', ['date' => $today]) }}" class="btn btn-sm btn-primary">
        Lihat Audit Lengkap
      </a>
      @hasanyrole('guru_piket')
      <a href="{{ route('audit.attendance.index', request()->only(['date','status','classroom_id'])) }}"
      class="btn btn-outline-primary btn-sm">
        <i class="bx bx-globe"></i> Audit Presensi Umum
      </a>
@endhasanyrole
    </div>
  </div>
</div>

@php
  $rekap = $rekapStatusWali ?? collect();
  $num = fn($k) => (int)($rekap[$k] ?? 0);
@endphp

<div class="row g-3 mb-3">
  @foreach ([
    ['key'=>'hadir','label'=>'Hadir','bs'=>'success','icon'=>'bx-check-circle'],
    ['key'=>'belum','label'=>'Belum Presensi','bs'=>'secondary','icon'=>'bx-minus-circle'],
    ['key'=>'terlambat','label'=>'Terlambat','bs'=>'warning','icon'=>'bx-time'],
    ['key'=>'sakit','label'=>'Sakit','bs'=>'primary','icon'=>'bx-first-aid'],
    ['key'=>'izin','label'=>'Izin','bs'=>'info','icon'=>'bx-edit'],
    ['key'=>'alpa','label'=>'Alpa','bs'=>'danger','icon'=>'bx-x-circle'],
  ] as $c)
  <div class="col-6 col-md-4">
    <a class="text-decoration-none"
       href="{{ route('wali.audit.attendance.index', ['date'=>$today, 'status'=>$c['key']]) }}">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex align-items-center gap-2">
          <div>
            <h4 class="mb-1">{{ $num($c['key']) }}</h4>
            <small class="text-muted">{{ $c['label'] }}</small>
          </div>
          <span class="badge bg-label-{{ $c['bs'] }} rounded p-2 ms-auto">
            <i class="bx {{ $c['icon'] }}"></i>
          </span>
        </div>
      </div>
    </a>
  </div>
  @endforeach
</div>

<div class="row">
  <div class="col-lg-5 mb-4 mb-lg-0">
    <h6 class="mb-3">Top 5 Siswa Terlambat (Bulan ini)</h6>
    <div class="mt-2 mb-3">
      <a href="{{ route('wali.audit.attendance.index', ['date'=>$today, 'status'=>'terlambat']) }}"
         class="btn btn-sm btn-outline-secondary">
        Lihat detail keterlambatan
      </a>
    </div>
    <ul class="list-group">
      @php $tops = $topSiswaWali ?? collect(); @endphp
      @forelse ($tops as $i => $item)
        <li class="list-group-item">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="badge bg-label-secondary me-2">{{ $i+1 }}</span>
              {{ $item->siswa->nama_lengkap ?? 'Siswa' }}
            </div>
            <span class="badge bg-label-warning">{{ (int)($item->terlambat_total ?? 0) }}x</span>
          </div>
          @if(!empty($item->last_date) || !empty($item->last_time))
            <small class="text-muted">
              Terakhir:
              @if(!empty($item->last_date))
                {{ \Illuminate\Support\Carbon::parse($item->last_date)->format('d-m') }}
              @endif
              @if(!empty($item->last_time))
                {{ \Illuminate\Support\Carbon::parse($item->last_time)->format('H:i') }}
              @endif
            </small>
          @endif
        </li>
      @empty
        <li class="list-group-item text-muted">Belum ada data bulan ini.</li>
      @endforelse
    </ul>
  </div>

  <div class="col-lg-7">
    <h6 class="mb-3">Aktivitas Presensi Terbaru ({{ $namaKelasWali }})</h6>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th style="width:110px;">Waktu</th>
            <th>Nama</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @php $recent = $recentActivitiesWali ?? collect(); @endphp
          @forelse ($recent as $row)
            @php
              $map = ['hadir'=>'success','terlambat'=>'warning','izin'=>'info','sakit'=>'primary','alpa'=>'danger'];
              $lbl = $map[$row->status] ?? 'secondary';
            @endphp
            <tr>
              <td>{{ \Illuminate\Support\Carbon::parse($row->time)->format('H:i:s') }}</td>
              <td>{{ $row->siswa->nama_lengkap ?? '-' }}</td>
              <td><span class="badge bg-label-{{ $lbl }}">{{ ucfirst($row->status) }}</span></td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-muted">Belum ada aktivitas hari ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
