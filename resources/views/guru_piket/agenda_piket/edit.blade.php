@extends('layouts.app')
@section('content')
<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> /
  <a href="{{route('agenda_piket.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Edit Data</span>
</h4>
<form action="{{ route('agenda_piket.update', $agendaPiket->id) }}" method="POST">
  @csrf
  @method('PUT')
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <div class="row g-3">
            @php
            $rows = $agendaPiket->guruKbmAbsences()->with('guru:id,nama_lengkap')->get();
            @endphp
            
            <div class="col-12">
              <h6 class="mb-2">Guru yang Tidak Melaksanakan KBM</h6>
              
              <div id="absensi-guru-rows">
                @forelse($rows as $i => $row)
                <div class="row g-2 align-items-end absensi-row">
                  <div class="col-md-5">
                    <label class="form-label">Guru</label>
                    <select name="absensi_guru[{{ $i }}][guru_id]" class="form-select select2">
                      <option value="">— Pilih Guru —</option>
                      @foreach(\App\Models\Guru::orderBy('nama_lengkap')->get() as $g)
                      <option value="{{ $g->id }}" @selected($g->id == $row->guru_id)>{{ $g->nama_lengkap }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="absensi_guru[{{ $i }}][status]" class="form-select">
                      <option value="">— Pilih —</option>
                      <option value="sakit" @selected($row->status==='sakit')>Sakit</option>
                      <option value="izin"  @selected($row->status==='izin')>Izin</option>
                      <option value="alpa"  @selected($row->status==='alpa')>Alpa</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Keterangan (opsional)</label>
                    <input type="text" name="absensi_guru[{{ $i }}][keterangan]" class="form-control" value="{{ $row->keterangan }}">
                  </div>
                  <div class="col-md-1 d-grid">
                    <button type="button" class="btn btn-outline-danger remove-row" {{ $loop->count===1 ? 'disabled' : '' }}>
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </div>
                @empty
                {{-- fallback jika belum ada data: render satu baris kosong (seperti create) --}}
                <div class="row g-2 align-items-end absensi-row">
                  <div class="col-md-5">
                    <label class="form-label">Guru</label>
                    <select name="absensi_guru[0][guru_id]" class="form-select">
                      <option value="">— Pilih Guru —</option>
                      @foreach(\App\Models\Guru::orderBy('nama_lengkap')->get() as $g)
                      <option value="{{ $g->id }}">{{ $g->nama_lengkap }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="absensi_guru[0][status]" class="form-select">
                      <option value="">— Pilih —</option>
                      <option value="sakit">Sakit</option>
                      <option value="izin">Izin</option>
                      <option value="alpa">Alpa</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Keterangan (opsional)</label>
                    <input type="text" name="absensi_guru[0][keterangan]" class="form-control">
                  </div>
                  <div class="col-md-1 d-grid">
                    <button type="button" class="btn btn-outline-danger remove-row" disabled>
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </div>
                @endforelse
              </div>
              <div class="mt-2">
                <button type="button" class="btn btn-outline-primary" id="add-absensi-row">
                  <i class="bx bx-plus me-1"></i> Tambah
                </button>
              </div>
            </div>
            <div>
              <label for="guru_piket" class="form-label">Guru Piket</label>
              <select name="guru_piket[]" class="form-select select2" multiple data-allow-clear="true" required>
                @foreach($guruPiket as $guru)
                <option value="{{ $guru->guru->id }}" {{ in_array($guru->guru->id,
                  json_decode($agendaPiket->guru_piket)) ? 'selected' : '' }}>
                  {{ $guru->guru->nama_lengkap }}
                </option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label for="tanggal" class="form-label">Tanggal</label>
              <input type="date" name="tanggal" class="form-control" value="{{ $agendaPiket->tanggal }}" required>
            </div>
            <div class="mb-3">
              <label for="kejadian_normal" class="form-label">Kejadian Normal</label>
              <textarea name="kejadian_normal" class="form-control">{{ $agendaPiket->kejadian_normal }}</textarea>
            </div>
            <div class="mb-3">
              <label for="kejadian_masalah" class="form-label">Kejadian Masalah</label>
              <textarea name="kejadian_masalah" class="form-control">{{ $agendaPiket->kejadian_masalah }}</textarea>
            </div>
            <div class="mb-3">
              <label for="solusi" class="form-label">Solusi</label>
              <textarea name="solusi" class="form-control">{{ $agendaPiket->solusi }}</textarea>
            </div>
          </div>
          <hr>
          <div class="d-flex justify-content-end mt-3 gap-2">
            <a href="{{ route('agenda_piket.index') }}" class="btn btn-secondary">
              <i class="bx bx-x me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-2"></i>Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
(function(){
  let idx = 1;
  const container = document.getElementById('absensi-guru-rows');
  const addBtn = document.getElementById('add-absensi-row');

  addBtn.addEventListener('click', () => {
    const row = container.querySelector('.absensi-row').cloneNode(true);
    row.querySelectorAll('select, input').forEach(el => {
      if (el.name.includes('absensi_guru')) {
        el.name = el.name.replace(/\[\d+\]/, '['+idx+']');
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        if (el.tagName === 'INPUT') el.value = '';
      }
    });
    row.querySelector('.remove-row').disabled = false;
    container.appendChild(row);
    idx++;
  });

  container.addEventListener('click', (e) => {
    if (e.target.closest('.remove-row')) {
      const rows = container.querySelectorAll('.absensi-row');
      if (rows.length > 1) e.target.closest('.absensi-row').remove();
    }
  });
})();
</script>
@endpush