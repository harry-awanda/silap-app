@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('guru-piket.outpasses.index') }}">Izin Keluar</a> /
  <span class="text-muted fw-light">Buat Baru</span>
</h4>

<div class="card col-lg-8 mx-auto">
  <div class="card-body">
    <form method="POST" action="{{ route('guru-piket.outpasses.store') }}">
      @csrf

      <div class="mb-3">
        <label class="form-label">Kelas</label>
        <select name="classroom_id" class="form-select" required id="classroom">
          <option value="">-- Pilih Kelas --</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}">{{ $c->nama_kelas }}</option>
          @endforeach
        </select>
        @error('classroom_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="mb-3">
        <label class="form-label">Siswa (bisa lebih dari satu)</label>
        <select name="siswa_ids[]" id="siswa" class="form-select" multiple required></select>
        <div class="form-text">Pilih kelas terlebih dahulu untuk memuat daftar siswa.</div>
        @error('siswa_ids')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Tujuan</label>
          <input type="text" name="destination" class="form-control" required value="{{ old('destination') }}">
          @error('destination')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
          <label class="form-label">Jenis Izin</label>
          <select name="reason" class="form-select">
            @foreach(\App\Models\OutPass::REASONS as $key => $label)
              <option value="{{ $key }}" @selected(old('reason')===$key)>{{ $label }}</option>
            @endforeach
          </select>
          @error('reason')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <label class="form-label">Jam Keluar</label>
          <input type="datetime-local" name="time_out" class="form-control" required value="{{ old('time_out') }}">
          @error('time_out')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" name="return_expected" id="returnExpected" {{ old('return_expected', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="returnExpected">Diharapkan kembali?</label>
          </div>
        </div>
      </div>

      <hr>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Metode Persetujuan</label>
          <select name="approval_method" class="form-select" required>
            @foreach(\App\Models\OutPass::METHODS as $key => $label)
              <option value="{{ $key }}" @selected(old('approval_method')===$key)>{{ $label }}</option>
            @endforeach
          </select>
          @error('approval_method')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Waktu Disetujui</label>
          <input type="datetime-local" name="approval_at" class="form-control" required value="{{ old('approval_at') }}">
          @error('approval_at')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Wali Kelas</label>
          <input type="text" name="approved_by_name" class="form-control" placeholder="Otomatis/isi manual" value="{{ old('approved_by_name') }}">
          @error('approved_by_name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a href="{{ route('guru-piket.outpasses.index') }}" class="btn btn-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  document.getElementById('classroom').addEventListener('change', async function(){
    const kelasId = this.value;
    const siswaSel = document.getElementById('siswa');
    siswaSel.innerHTML = '';
    if(!kelasId) return;
    const resp = await fetch(`/api/classrooms/${kelasId}/students`); // buat endpoint JSON kecil
    if(!resp.ok) return;
    const data = await resp.json();
    data.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.nama_lengkap;
      siswaSel.appendChild(opt);
    });
  });

  document.querySelector('select[name="reason"]').addEventListener('change', function(){
    const isSick = this.value === 'sakit_pulang';
    const cb = document.getElementById('returnExpected');
    cb.checked = !isSick;
  });
</script>
@endpush
@endsection