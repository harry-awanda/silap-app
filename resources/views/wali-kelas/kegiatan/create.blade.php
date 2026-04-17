@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('kegiatan-absensi.index', ['tanggal' => $tanggal]) }}">{{ $title ?? 'Absensi Kegiatan Pagi' }}</a> /
  <span class="text-muted fw-light">Input/Ubah Absensi</span>
</h4>

{{-- =========================
  CARD FILTER (Tanggal + Kegiatan)
========================= --}}
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" action="{{ route('kegiatan-absensi.create') }}" class="row g-2 align-items-end">
      <div class="col-12 col-md-3">
        <label class="form-label">Tanggal</label>
        <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}">
      </div>

      <div class="col-12 col-md-4">
        <label class="form-label">Kegiatan</label>
        <select name="activity_id" id="activitySelectGet" class="form-select">
          <option value="">Pilih kegiatan</option>
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
          <i class="bx bx-filter-alt me-2"></i>Tampilkan
        </button>
      </div>
    </form>

    @if (!$selectedActivityId)
      <div class="alert alert-info mt-3 mb-0">
        Pilih tanggal & kegiatan terlebih dahulu, lalu klik <strong>Tampilkan</strong>.
      </div>
    @endif
  </div>
</div>

{{-- =========================
  CARD INPUT ABSENSI (muncul setelah filter lengkap)
========================= --}}
@if ($selectedActivityId)
  <div class="card">
    <div class="card-body">

      <div class="mb-3">
        <h4 class="mb-1">Kelas {{ $classroom->nama_kelas }}</h4>
        <small class="text-muted">
          Tanggal: {{ \Illuminate\Support\Carbon::parse($tanggal)->format('d-m-Y') }}
        </small>

        @if(!empty($activity))
          <div class="mt-2">
            <span class="badge bg-label-primary">
              Kegiatan: {{ $activity->nama }}
            </span>
          </div>
        @endif
      </div>

      <form method="POST" action="{{ route('kegiatan-absensi.store') }}">
        @csrf

        {{-- yang disimpan --}}
        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
        <input type="hidden" name="morning_activity_id" value="{{ $selectedActivityId }}">

        {{-- kalau Anda punya fitur "kegiatan kustom", aktifkan bagian ini
            (saat ini dibuat selalu hidden agar tidak membingungkan) --}}
        <input type="hidden" name="custom_activity_name" value="{{ old('custom_activity_name', $existingCustomName ?? '') }}">

        <div class="mb-2 d-flex flex-wrap gap-2">
          <button type="button" id="mark-all-hadir" class="btn btn-sm btn-success">
            Tandai Semua Hadir
          </button>
          <button type="button" id="mark-all-tidak" class="btn btn-sm btn-danger">
            Tandai Semua Tidak Hadir
          </button>
        </div>

        <div class="table-responsive table-sticky">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:60px">#</th>
                <th style="width:120px">NIS</th>
                <th>Nama</th>
                <th style="width:220px">Status</th>
                <th style="width:260px">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($siswas as $i => $s)
                @php
                  $exist   = $existing->get($s->id);
                  // ✅ sinkron dengan controller: hadir / tidak_hadir
                  $isHadir = $exist ? ($exist->status === 'hadir') : true;
                @endphp
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>{{ $s->nis }}</td>
                  <td>{{ $s->nama_lengkap }}</td>
                  <td>
                    <div class="btn-group" role="group" aria-label="status">
                      <input type="radio"
                        class="btn-check"
                        name="status[{{ $s->id }}]"
                        id="hadir-{{ $s->id }}"
                        value="hadir"
                        @checked($isHadir)>
                      <label class="btn btn-outline-success btn-sm" for="hadir-{{ $s->id }}">Hadir</label>

                      <input type="radio"
                        class="btn-check"
                        name="status[{{ $s->id }}]"
                        id="tdk-{{ $s->id }}"
                        value="tidak_hadir"
                        @checked(!$isHadir)>
                      <label class="btn btn-outline-danger btn-sm" for="tdk-{{ $s->id }}">Tidak</label>
                    </div>
                  </td>
                  <td>
                    <input type="text"
                      name="keterangan[{{ $s->id }}]"
                      class="form-control form-control-sm"
                      value="{{ old("keterangan.$s->id", $exist->keterangan ?? '') }}"
                      placeholder="(opsional)">
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <hr>

        <div class="mt-3 d-flex flex-wrap gap-2">
          <a class="btn btn-label-secondary" href="{{ route('kegiatan-absensi.index', ['tanggal' => $tanggal]) }}">
            <i class="bx bx-chevrons-left me-2"></i>Kembali
          </a>

          <button class="btn btn-primary">
            <i class="bx bx-save me-2"></i>Simpan Absensi
          </button>
        </div>

        @if ($errors->any())
          <div class="alert alert-danger mt-3 mb-0">
            @foreach ($errors->all() as $e)
              <div>{{ $e }}</div>
            @endforeach
          </div>
        @endif
      </form>

    </div>
  </div>
@endif
@endsection

@push('styles')
<style>
.table-sticky { max-height: 420px; overflow: auto; }
.table-sticky thead th {
  position: sticky; top: 0; background: var(--bs-body-bg);
  z-index: 5; box-shadow: 0 2px 0 rgba(0,0,0,.03);
}
.table-sticky table { border-collapse: separate; }
</style>
@endpush

@push('scripts')
<script>
(function(){
  // Tandai massal - sesuaikan dengan value yang dikirim ke controller
  document.getElementById('mark-all-hadir')?.addEventListener('click', () => {
    document.querySelectorAll('input[type=radio][value="hadir"]').forEach(r => r.checked = true);
  });

  document.getElementById('mark-all-tidak')?.addEventListener('click', () => {
    document.querySelectorAll('input[type=radio][value="tidak_hadir"]').forEach(r => r.checked = true);
  });
})();
</script>
@endpush
