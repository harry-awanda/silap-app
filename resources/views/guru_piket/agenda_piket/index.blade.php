@extends('layouts.app')

@section('content')
@include('layouts.toasts')

@php
  use App\Models\AcademicTerm;
  $activeTerm = AcademicTerm::where('is_active', true)->first();
@endphp

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
  @if($activeTerm)
    <span class="badge bg-label-info ms-2">
      {{ $activeTerm->year_start }}/{{ $activeTerm->year_end }} -
      Semester {{ ucfirst($activeTerm->semester) }}
    </span>
  @endif
</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Daftar Agenda Piket</h5>
    <a href="{{ route('agenda_piket.create') }}" class="btn btn-primary">
      <i class="bx bx-plus me-1"></i> Tambah Agenda
    </a>
  </div>

  <div class="card-body">
    <div class="table-responsive text-nowrap table-hover">
      <table class="table datatable align-middle">
        <thead class="table-light">
          <tr>
            <th class="text-center" width="60">#</th>
            <th class="text-center" width="120">Pilihan</th>
            <th>Tanggal</th>
            <th>Guru Piket</th>
          </tr>
        </thead>

        <tbody>
          @foreach($agendaPikets as $data)
            @php
              $guruPiket = collect(json_decode($data->guru_piket, true))
                ->map(fn($id) => \App\Models\Guru::find($id)?->nama_lengkap)
                ->filter()
                ->join(', ');
            @endphp
            <tr>
              <td class="text-center">{{ $loop->iteration }}</td>
              <td class="text-center">
                <div class="dropdown">
                  <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('agenda_piket.export', $data->id) }}">
                      <i class="bx bx-file me-1"></i> Export PDF
                    </a>
                    <a class="dropdown-item" href="{{ route('agenda_piket.edit', $data->id) }}">
                      <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <form action="{{ route('agenda_piket.destroy', $data->id) }}" method="POST" class="d-inline">
                      @csrf @method('DELETE')
                      <button type="submit" class="dropdown-item"
                        onclick="return confirm('Apakah Anda yakin ingin menghapus agenda ini?')">
                        <i class="bx bx-trash me-1"></i> Hapus
                      </button>
                    </form>
                  </div>
                </div>
              </td>
              <td>{{ \Carbon\Carbon::parse($data->tanggal)->translatedFormat('l, d F Y') }}</td>
              <td>{{ $guruPiket ?: '-' }}</td>

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
  new DataTable('.datatable', {
    order: [[1, 'desc']],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' },
    pageLength: 10
  });
</script>
@endpush
