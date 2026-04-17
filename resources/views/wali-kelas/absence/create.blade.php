@extends('layouts.app')

@section('content')
  @include('layouts.toasts')

  <h4 class="py-3 mb-4">
    <a href="{{ route('dashboard') }}">Dashboard</a> /
    <a href="{{ route('absence.index', ['date' => $date]) }}">Absensi</a> /
    <span class="text-muted fw-light">Tambah / Edit</span>
  </h4>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  
  {{-- Toolbar ubah tanggal --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex flex-column gap-2">
        <div>
          <h5 class="mb-0">Tanggal: {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }}</h5>
          <small class="text-muted">Pilih untuk mengisi pada tanggal tertentu (backdate).</small>
        </div>
  
        <form method="GET" action="{{ route('absence.create') }}"
          class="d-flex flex-wrap align-items-center gap-2 mt-1">
          <input type="date" name="date" class="form-control"
            value="{{ $date }}" max="{{ now()->toDateString() }}" style="max-width: 220px;">
          <button class="btn btn-outline-primary" type="submit">
            <i class="bx bx-calendar-event me-1"></i> Ubah tanggal
          </button>
        </form>
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('absence.store') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Input Ketidakhadiran</h5>
        <small class="text-muted">Tanggal: {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }}</small>
      </div>

      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle datatable w-100">
            <thead>
              <tr>
                <th>Nama Siswa</th>
                <th class="text-nowrap">NIS</th>
                <th class="text-center" style="width:80px;">Sakit</th>
                <th class="text-center" style="width:80px;">Izin</th>
                <th class="text-center" style="width:80px;">Alpa</th>
              </tr>
            </thead>
            <tbody>
              @foreach($siswa as $s)
                @php
                  $oldStatus = old("absence.$s->id.status");
                  $checked   = $oldStatus !== null ? $oldStatus : ($absences[$s->id]->status ?? null);
                @endphp
                <tr>
                  <td class="text-wrap">
                    {{ $s->nama_lengkap }}
                    <input type="hidden" name="absence[{{ $s->id }}][siswa_id]" value="{{ $s->id }}">
                  </td>
                  <td class="text-nowrap">{{ $s->nis }}</td>

                  <td class="text-center">
                    @php $id = "sakit-$s->id"; @endphp
                    <input id="{{ $id }}" type="radio" class="form-check-input"
                           name="absence[{{ $s->id }}][status]" value="sakit"
                           {{ $checked === 'sakit' ? 'checked' : '' }}>
                    <label for="{{ $id }}" class="visually-hidden">Sakit</label>
                  </td>

                  <td class="text-center">
                    @php $id = "izin-$s->id"; @endphp
                    <input id="{{ $id }}" type="radio" class="form-check-input"
                           name="absence[{{ $s->id }}][status]" value="izin"
                           {{ $checked === 'izin' ? 'checked' : '' }}>
                    <label for="{{ $id }}" class="visually-hidden">Izin</label>
                  </td>

                  <td class="text-center">
                    @php $id = "alpa-$s->id"; @endphp
                    <input id="{{ $id }}" type="radio" class="form-check-input"
                           name="absence[{{ $s->id }}][status]" value="alpa"
                           {{ $checked === 'alpa' ? 'checked' : '' }}>
                    <label for="{{ $id }}" class="visually-hidden">Alpa</label>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('absence.index', ['date' => $date]) }}" class="btn btn-secondary">
            <i class="bx bx-x me-1"></i> Batal
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-2"></i> Simpan
          </button>
        </div>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
<script>
  if (typeof DataTable === 'function') {
    let table = new DataTable('.datatable');
  }
</script>
@endpush