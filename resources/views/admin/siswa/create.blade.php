@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.siswa.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Tambah Data</span>
</h4>

<div class="row">
  <div class="col-md-12">
    <div class="card mb-4">
      <div class="col-lg-10 mx-auto">

        {{-- Wizard Header / Step Indicator --}}
        <div class="card-header">
          <div class="d-flex align-items-center gap-3">
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between small mb-2">
                <span class="fw-semibold" id="wizardStepText">Langkah 1 dari 2</span>
                <span class="text-muted" id="wizardStepLabel">Data Siswa</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar" id="wizardProgress" role="progressbar" style="width: 50%;"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body">
          <form id="wizardForm" action="{{ route('admin.siswa.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            {{-- ====================== STEP 1: DATA SISWA ====================== --}}
            <div class="wizard-step" data-step="1">
              <h5 class="mb-4">1. Data Siswa</h5>

              <div class="d-flex align-items-start align-items-sm-center gap-4 mb-4">
                <img id="photoPreview" src="{{ asset('assets/img/avatars/1.png') }}" alt="user-avatar" class="d-block rounded" height="100" />
                <div class="button-wrapper">
                  <label for="upload" class="btn btn-primary me-2 mb-2" tabindex="0">
                    <span class="d-none d-sm-inline">Upload Foto</span>
                    <i class="bx bx-upload d-inline d-sm-none"></i>
                    <input id="upload" type="file" name="photo" class="account-file-input" hidden accept="image/png, image/jpeg" />
                  </label>
                  <p class="text-muted mb-0">Format JPG/PNG. Maks 800KB.</p>
                </div>
              </div>

              <div class="row">
                <div class="mb-3 col-md-6">
                  <label for="nis" class="form-label">NIS</label>
                  <input id="nis" type="text" name="nis" class="form-control" placeholder="8867" value="{{ old('nis') }}" required>
                </div>
                <div class="mb-3 col-md-6">
                  <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                  <input id="nama_lengkap" type="text" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" required>
                </div>
                <div class="mb-3 col-md-6">
                  <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                  <input id="tempat_lahir" type="text" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir') }}">
                </div>
                <div class="mb-3 col-md-6">
                  <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                  <input id="tanggal_lahir" type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir') }}">
                </div>
                <div class="mb-3 col-md-6">
                  <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                  <select id="jenis_kelamin" name="jenis_kelamin" class="form-select">
                    <option value="" {{ old('jenis_kelamin')==''?'selected':'' }}>-- Pilih --</option>
                    <option value="L" {{ old('jenis_kelamin')=='L'?'selected':'' }}>Laki-laki</option>
                    <option value="P" {{ old('jenis_kelamin')=='P'?'selected':'' }}>Perempuan</option>
                  </select>
                </div>
                <div class="mb-3 col-md-6">
                  <label for="agama" class="form-label">Agama</label>
                  <select id="agama" name="agama" class="form-select">
                    <option value="" {{ old('agama')==''?'selected':'' }}>-- Pilih --</option>
                    @foreach(['Islam','Buddha','Hindu','Kristen Katolik','Kristen Protestan'] as $agama)
                      <option value="{{ $agama }}" {{ old('agama')==$agama?'selected':'' }}>{{ $agama }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="mb-3 col-md-6">
                  <label for="kontak" class="form-label">Nomor Telepon / Whatsapp</label>
                  <div class="input-group">
                    <span class="input-group-text">+62</span>
                    <input id="kontak" type="text" name="kontak" class="form-control" placeholder="812 3456 7890" value="{{ old('kontak') }}">
                  </div>
                </div>

                @if($classrooms->isEmpty())
                  <div class="col-12">
                    <div class="alert alert-warning">Anda tidak memiliki akses ke kelas manapun.</div>
                  </div>
                @else
                  <div class="mb-3 col-md-6">
                    <label for="classroom_id" class="form-label">Kelas</label>
                    <select id="classroom_id" name="classroom_id" class="form-select select2" required>
                      <option value="" disabled {{ old('classroom_id') ? '' : 'selected' }}>-- Pilih --</option>
                      @foreach($classrooms as $data)
                        <option value="{{ $data->id }}" {{ old('classroom_id') == $data->id ? 'selected' : '' }}>
                          {{ $data->nama_kelas }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                @endif

                <div class="mb-3 col-md-6">
                  <label for="alamat" class="form-label">Alamat</label>
                  <textarea id="alamat" name="alamat" class="form-control" rows="2">{{ old('alamat') }}</textarea>
                </div>
              </div>

              <div class="d-flex justify-content-end mt-2">
                <button type="button" class="btn btn-primary" data-next>Berikutnya</button>
              </div>
            </div>

            {{-- ====================== STEP 2: DATA ORANG TUA/WALI ====================== --}}
            <div class="wizard-step d-none" data-step="2">
              <h5 class="mb-4">2. Data Orang Tua</h5>

              <div class="row">
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="nama_ayah">Nama Ayah</label>
                  <input id="nama_ayah" type="text" name="nama_ayah" class="form-control" value="{{ old('nama_ayah') }}">
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="nama_ibu">Nama Ibu</label>
                  <input id="nama_ibu" type="text" name="nama_ibu" class="form-control" value="{{ old('nama_ibu') }}">
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="pekerjaan_ayah">Pekerjaan Ayah</label>
                  <input id="pekerjaan_ayah" type="text" name="pekerjaan_ayah" class="form-control" value="{{ old('pekerjaan_ayah') }}">
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="pekerjaan_ibu">Pekerjaan Ibu</label>
                  <input id="pekerjaan_ibu" type="text" name="pekerjaan_ibu" class="form-control" value="{{ old('pekerjaan_ibu') }}">
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="kontak_ayah">Nomor Telepon / Whatsapp Ayah</label>
                  <div class="input-group">
                    <span class="input-group-text">+62</span>
                    <input id="kontak_ayah" type="text" name="kontak_ayah" class="form-control" placeholder="812 3456 7890" value="{{ old('kontak_ayah') }}">
                  </div>
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="kontak_ibu">Nomor Telepon / Whatsapp Ibu</label>
                  <div class="input-group">
                    <span class="input-group-text">+62</span>
                    <input id="kontak_ibu" type="text" name="kontak_ibu" class="form-control" placeholder="812 3456 7890" value="{{ old('kontak_ibu') }}">
                  </div>
                </div>

                <div class="mb-3 col-md-6">
                  <label class="form-label" for="nama_wali_murid">Nama Wali Murid</label>
                  <input id="nama_wali_murid" type="text" name="nama_wali_murid" class="form-control" value="{{ old('nama_wali_murid') }}">
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="kontak_wali">Nomor Telepon / Whatsapp Wali</label>
                  <div class="input-group">
                    <span class="input-group-text">+62</span>
                    <input id="kontak_wali" type="text" name="kontak_wali" class="form-control" placeholder="812 3456 7890" value="{{ old('kontak_wali') }}">
                  </div>
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="alamat_orangtua">Alamat Orang Tua</label>
                  <textarea id="alamat_orangtua" name="alamat_orangtua" class="form-control" rows="2">{{ old('alamat_orangtua') }}</textarea>
                </div>
                <div class="mb-3 col-md-6">
                  <label class="form-label" for="alamat_wali">Alamat Wali Murid</label>
                  <textarea id="alamat_wali" name="alamat_wali" class="form-control" rows="2">{{ old('alamat_wali') }}</textarea>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-2">
                <button type="button" class="btn btn-outline-secondary" data-prev>Sebelumnya</button>
                <div>
                  <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
              </div>
            </div>

          </form>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
  const steps = Array.from(document.querySelectorAll('.wizard-step'));
  const progress = document.getElementById('wizardProgress');
  const stepText = document.getElementById('wizardStepText');
  const stepLabel = document.getElementById('wizardStepLabel');
  const labels = { 1: 'Data Siswa', 2: 'Data Orang Tua' };
  let current = 1;
  const total = steps.length;

  function showStep(n) {
    steps.forEach(s => s.classList.add('d-none'));
    steps.find(s => Number(s.dataset.step) === n)?.classList.remove('d-none');
    progress.style.width = (n / total * 100) + '%';
    stepText.textContent = `Langkah ${n} dari ${total}`;
    stepLabel.textContent = labels[n];
    current = n;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.querySelectorAll('[data-next]').forEach(btn => {
    btn.addEventListener('click', () => {
      showStep(current + 1);
    });
  });

  document.querySelectorAll('[data-prev]').forEach(btn => {
    btn.addEventListener('click', () => {
      showStep(current - 1);
    });
  });

  document.getElementById('btnResetWizard')?.addEventListener('click', () => {
    setTimeout(() => showStep(1), 0);
  });

  showStep(1);
})();
</script>
@endpush