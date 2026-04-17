@extends('layouts.app')
@section('content')

@php $range = $range ?? request('range'); @endphp

<h4 class="py-3 mb-1">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span>Rekap Absensi — {{ $classroom->nama_kelas }}</span>
</h4>

<div class="card">
  <div class="card-header">
    <h6 class="mb-4">Periode Akademik:
      <strong>{{ $term->name ?? ($term->code ?? 'Term '.$term->id) }}</strong>
      ({{ \Carbon\Carbon::parse($term->start_date)->isoFormat('D MMM YYYY') }}
      – {{ \Carbon\Carbon::parse($term->end_date)->isoFormat('D MMM YYYY') }})
    </h6>
      
    <form method="GET" action="{{ route('monthlyRecap') }}" class="w-100">
      <div class="d-flex flex-wrap gap-2 align-items-end">
        <input type="hidden" name="term_id" value="{{ request('term_id', $term->id) }}">

        {{-- Rentang --}}
        <div>
          <label for="range" class="form-label mb-1">Rentang</label>
          <select id="range" name="range" class="form-select">
            <option value=""     {{ empty($range) ? 'selected' : '' }}>Bulanan</option>
            <option value="TERM" {{ ($range ?? '')==='TERM' ? 'selected' : '' }}>Semester Aktif</option>
          </select>
        </div>

        {{-- Bulan (disable saat range = TERM) --}}
        <div>
          <label for="month" class="form-label mb-1">Bulan</label>
          <select id="month" name="month" class="form-select">
            @foreach($bulanIndonesia as $key => $bulan)
              <option value="{{ $key }}" {{ (int)$month === (int)$key ? 'selected' : '' }}>
                {{ $bulan }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Tahun (bebas pilih; tetap di-clamp oleh controller) --}}
        <div>
          <label for="year" class="form-label mb-1">Tahun</label>
          <select id="year" name="year" class="form-select">
            @foreach(range(date('Y') - 1, date('Y') + 1) as $y)
              <option value="{{ $y }}" {{ (int)$year === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
          </select>
        </div>

        {{-- Tombol --}}
        <div>
          <div class="btn-group" role="group">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-show me-2"></i>Tampilkan
            </button>
            <a href="{{ route('monthlyRecap.export', [
                    'term_id' => request('term_id', $term->id),
                    'month' => $month,
                    'year'  => $year,
                    'range' => ($range ?? request('range'))
                ]) }}" class="btn btn-success">
              <i class="bx bx-spreadsheet me-2"></i>Export
            </a>
          </div>
        </div>

      </div>
    </form>
    <!-- <div class="small text-muted mt-2">
      Menampilkan data antara
      <strong>{{ $startDate->isoFormat('D MMM YYYY') }}</strong>
      – <strong>{{ $endDate->isoFormat('D MMM YYYY') }}</strong>
      (ter-clamp ke rentang term aktif).
    </div> -->
  </div>

  <div class="card-body">
    <div class="table-responsive table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th>Nama Siswa</th>
            <th>Sakit</th>
            <th>Izin</th>
            <th>Alpa</th>
          </tr>
        </thead>
        <tbody>
          @foreach($siswa as $data)
            <tr>
              <td>{{ $data->nama_lengkap }}</td>
              <td>{{ $rekapAbsensi[$data->id]['sakit'] ?? 0 }}</td>
              <td>{{ $rekapAbsensi[$data->id]['izin']  ?? 0 }}</td>
              <td>{{ $rekapAbsensi[$data->id]['alpa']  ?? 0 }}</td>
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
  let table = new DataTable('.datatable');

  const rangeSel = document.getElementById('range');
  const monthSel = document.getElementById('month');

  function toggleMonth() {
    if (!rangeSel) return;
    if (rangeSel.value === 'TERM') {
      monthSel?.setAttribute('disabled', 'disabled');
    } else {
      monthSel?.removeAttribute('disabled');
    }
  }
  rangeSel?.addEventListener('change', toggleMonth);
  toggleMonth();
</script>
@endpush
