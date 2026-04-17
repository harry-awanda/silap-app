@extends('layouts.app')

@section('content')
@include('layouts.toasts')

@php
  $homeroom = request()->attributes->get('homeroom');
  $current  = $homeroom?->classroom;

  // term aktif (kalau InjectActiveTerm ada)
  $activeTerm = request()->attributes->get('activeTerm') ?? app('activeTerm') ?? null;
  $isGenap = $activeTerm && strtolower($activeTerm->semester) === 'genap';

  $termLabel = $activeTerm
    ? ($activeTerm->year_start.'/'.$activeTerm->year_end.' - '.ucfirst($activeTerm->semester))
    : '-';

  // graduation hanya kelas 12 dan semester genap
  $isGraduation = $current && (int)$current->tingkat === 12 && $isGenap;

  $promoteLabel = $isGraduation ? 'Kelulusan Siswa' : 'Promote';
  $promoteMode  = $isGraduation ? 'graduate' : 'promote';

  // view tersimpan dari controller
  $currentView = $view ?? 'list';

  $isPageOne = request()->query('page', 1) == 1;
@endphp

{{-- Breadcrumb --}}
<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title ?? 'Siswa Binaan' }}</span>
</h4>

<div class="card mb-3">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="d-flex flex-wrap align-items-center gap-2">
      <span class="badge bg-label-primary text-uppercase">
        KELAS: {{ $current?->nama_kelas ?? '-' }}
      </span>
    </div>

    <div class="d-flex align-items-center gap-2">
      {{-- Toggle view (pakai query ?view= agar tersimpan di session oleh controller) --}}
      <div class="btn-group" role="group" aria-label="Toggle view">
        <a id="btn-list"
           href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
           class="btn btn-outline-secondary {{ $currentView === 'list' ? 'active' : '' }}">
          <i class="bx bx-list-ul me-1"></i> List
        </a>
        <a id="btn-grid"
           href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
           class="btn btn-outline-secondary {{ $currentView === 'grid' ? 'active' : '' }}">
          <i class="bx bx-grid-alt me-1"></i> Grid
        </a>
      </div>

      <a href="{{ route('siswa.import') }}" class="btn btn-outline-primary">
        <i class="bx bx-upload me-1"></i> Import
      </a>

      <a href="{{ route('siswa.promosi.index', $promoteMode) }}" class="btn btn-warning">
        <i class="bx bx-transfer me-1"></i> {{ $promoteLabel }}
      </a>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">

    {{-- EMPTY STATE --}}
    @if($siswa->isEmpty())
      <div class="text-center py-5">
        <div class="mb-2">
          <i class="bx bx-user-x" style="font-size: 48px;"></i>
        </div>
        <div class="fw-semibold mb-1">Belum ada siswa di kelas ini (term aktif)</div>
        <div class="text-muted mb-3">
          Silakan import data siswa atau pastikan penempatan siswa pada term aktif sudah terisi.
        </div>
        <a href="{{ route('siswa.import') }}" class="btn btn-primary">
          <i class="bx bx-upload me-1"></i> Import Siswa
        </a>
      </div>
    @else

      {{-- LIST VIEW --}}
      @if($currentView === 'list')
        <div id="list-container">
          <div class="table-responsive text-nowrap table-hover">
            <table class="table datatable" id="siswaTable">
              <thead>
                <tr>
                  <th style="width:60px">#</th>
                  <th style="width:100px">Pilihan</th>
                  <th>NIS</th>
                  <th>Nama</th>
                  <th>JK</th>
                </tr>
              </thead>
              <tbody>
                @foreach($siswa as $row)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>
                    <div class="dropdown">
                      <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="bx bx-dots-vertical-rounded"></i>
                      </button>
                      <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('siswa.edit', $row->id) }}">
                          <i class="bx bx-user me-1"></i> Detail
                        </a>
                        <form action="{{ route('siswa.destroy', $row->id) }}" method="POST"
                        onsubmit="return confirm('Hapus siswa ini? Tindakan tidak dapat dibatalkan.');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="dropdown-item text-danger">
                            <i class="bx bx-trash me-1"></i> Hapus
                          </button>
                        </form>
                      </div>
                    </div>
                  </td>
                  <td>{{ $row->nis }}</td>
                  <td class="text-uppercase">{{ $row->nama_lengkap }}</td>
                  <td>{{ $row->jenis_kelamin }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

      {{-- GRID VIEW --}}
      @else
        <div id="grid-container" class="row g-3">
          @foreach($siswa as $row)
            <div class="col-6 col-md-4 col-lg-3 col-xl-3">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column align-items-center text-center p-3">

                  {{-- FOTO SISWA --}}
                  <div class="avatar avatar-xxl mb-3" style="width:150px;height:150px;">
                    @if($row->photo)
                      <img
                          src="{{ route('media', ['path' => $row->photo]) }}"
                          class="rounded-circle img-fluid border"
                          style="object-fit:cover;width:150px;height:150px;">
                    @else
                      <span class="avatar-initial rounded-circle bg-primary text-white border"
                            style="width:150px;height:150px;display:flex;align-items:center;justify-content:center;font-size:3rem;">
                        {{ strtoupper(mb_substr($row->nama_lengkap,0,1,'UTF-8')) }}
                      </span>
                    @endif
                  </div>

                  {{-- NAMA & NIS --}}
                  <div class="fw-semibold text-uppercase mb-1" style="max-width:180px;">
                    <span class="d-inline-block text-truncate" style="max-width:180px;">
                      {{ $row->nama_lengkap }}
                    </span>
                  </div>
                  <div class="text-muted small mb-3">{{ $row->nis }}</div>

                  {{-- AKSI --}}
                  <div class="mt-auto d-flex gap-1 justify-content-center">
                    <a href="{{ route('siswa.edit',$row->id) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Detail">
                      <i class="bx bx-user"></i>
                    </a>
                    <form action="{{ route('siswa.destroy', $row->id) }}" method="POST"
                          onsubmit="return confirm('Hapus siswa ini? Tindakan tidak dapat dibatalkan.');" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Hapus">
                        <i class="bx bx-trash"></i>
                      </button>
                    </form>
                  </div>

                </div>
              </div>
            </div>
          @endforeach
        </div>
        
        <div class="d-flex justify-content-center gap-2 mt-4">
          {{-- MUAT LAGI (hanya jika ada halaman berikutnya) --}}
          
          @if(!empty($nextUrl))
            <a href="{{ $nextUrl }}" class="btn btn-outline-secondary">
              Muat Lagi
            </a>
          @endif
          
          {{-- KEMBALI KE AWAL (hanya jika bukan halaman 1) --}}
          @if(!$isPageOne)
            <a href="{{ request()->url() }}?view=grid"
              class="btn btn-outline-primary">
              Kembali ke Awal
            </a>
          @endif
        </div>
        @endif
      @endif


  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // DataTables hanya untuk list view
  const currentView = @json($currentView);

  if (currentView === 'list') {
    new DataTable('.datatable', {
      perPage: 25,
      order: [[2, 'asc']]
    });
  }
});
</script>
@endpush
