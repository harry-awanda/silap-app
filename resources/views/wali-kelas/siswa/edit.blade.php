@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('siswa.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Profil Siswa</span>
</h4>

<div class="row">
  <div class="col-md-12">

    {{-- Tabs --}}
    <ul class="nav nav-pills flex-column flex-md-row mb-4">
      <li class="nav-item">
        <a class="nav-link active" href="{{ route('siswa.edit', $siswa->id) }}">
          <i class="bx bx-user me-1"></i> Profil
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('siswa.pelanggaran.index', $siswa->id) }}">
          <i class="bx bx-shield me-1"></i> Riwayat Pelanggaran
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('siswa.absensi.index', $siswa->id) }}">
          <i class="bx bx-calendar-x me-1"></i> Riwayat Absensi
        </a>
      </li>
    </ul>

    <div class="card mb-4">
      <div class="col-lg-8 mx-auto">
        <div class="card-body">

          <form method="POST" action="{{ route('siswa.update', $siswa->id) }}" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')

            {{-- Header profil + upload foto --}}
            <div class="d-flex align-items-center gap-4 mb-4">
              <div class="flex-shrink-0">
                @if($siswa->photo)
                  <img
                    id="photoPreview"
                    src="{{ route('media', ['path' => $siswa->photo]) }}"
                    class="rounded"
                    style="object-fit:cover;width:80px;height:80px;"
                    alt="Foto Siswa"
                  >
                  <span
                    id="photoInitial"
                    class="d-none avatar-initial rounded-circle bg-primary text-white"
                    style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;font-size:1.75rem;"
                  >
                    {{ strtoupper(mb_substr($siswa->nama_lengkap,0,1,'UTF-8')) }}
                  </span>
                @else
                  <img
                    id="photoPreview"
                    src=""
                    class="rounded d-none"
                    style="object-fit:cover;width:80px;height:80px;"
                    alt="Foto Siswa"
                  >
                  <span
                    id="photoInitial"
                    class="avatar-initial rounded-circle bg-primary text-white"
                    style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;font-size:1.75rem;"
                  >
                    {{ strtoupper(mb_substr($siswa->nama_lengkap,0,1,'UTF-8')) }}
                  </span>
                @endif
              </div>

              <div class="button-wrapper">
                {{-- file input disembunyikan; dipakai hanya untuk sumber ke cropper --}}
                <input id="upload" type="file" name="photo" class="account-file-input" hidden accept="image/png, image/jpeg" />
                {{-- hasil crop (base64) akan dikirim lewat ini --}}
                <input type="hidden" name="cropped" id="croppedInput" />

                <button type="button" class="btn btn-primary me-2 mb-2" id="btnChooseFile">
                  <span class="d-none d-sm-inline">Upload photo</span>
                  <i class='bx bx-upload ms-2 d-none d-sm-inline'></i>
                  <i class="bx bx-upload d-inline d-sm-none"></i>
                </button>

                <button type="button" class="btn btn-label-secondary mb-2" id="btnResetFile">
                  Reset
                </button>

                <p class="text-muted mb-0">Allowed JPG/PNG. Max size 800KB (hasil crop otomatis 1:1).</p>
              </div>
            </div>

            {{-- Form satu halaman (tanpa progress bar / stepper) --}}
            <div class="row">

              {{-- Data siswa --}}
              <div class="col-12">
                <h5 class="mb-3">Data Siswa</h5>
              </div>

              <div class="mb-3 col-md-6">
                <label for="nis" class="form-label">NIS</label>
                <input id="nis" type="text" name="nis" class="form-control" value="{{ old('nis', $siswa->nis) }}" required>
              </div>

              <div class="mb-3 col-md-6">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input id="nama_lengkap" type="text" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap', $siswa->nama_lengkap) }}" required>
              </div>

              <div class="mb-3 col-md-6">
                <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                <input id="tempat_lahir" type="text" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir', $siswa->tempat_lahir) }}">
              </div>

              <div class="mb-3 col-md-6">
                <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                <input id="tanggal_lahir" type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir', $siswa->tanggal_lahir?->toDateString()) }}">
              </div>

              <div class="mb-3 col-md-6">
                <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                @php $jk = old('jenis_kelamin', $siswa->jenis_kelamin); @endphp
                <select id="jenis_kelamin" name="jenis_kelamin" class="form-select">
                  <option value="">-- Pilih --</option>
                  <option value="L" {{ $jk=='L'?'selected':'' }}>Laki-laki</option>
                  <option value="P" {{ $jk=='P'?'selected':'' }}>Perempuan</option>
                </select>
              </div>

              <div class="mb-3 col-md-6">
                <label for="agama" class="form-label">Agama</label>
                @php $ag = old('agama', $siswa->agama); @endphp
                <select id="agama" name="agama" class="form-select">
                  <option value="">-- Pilih --</option>
                  @foreach(['Islam', 'Buddha', 'Hindu', 'Kristen Katolik', 'Kristen Protestan'] as $agama)
                    <option value="{{ $agama }}" {{ $ag==$agama ? 'selected' : '' }}>{{ $agama }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Kontak (col-6) --}}
              <div class="mb-3 col-md-6">
                <label for="kontak" class="form-label">Nomor Telepon / Whatsapp</label>
                <div class="input-group">
                  <span class="input-group-text">+62</span>
                  <input id="kontak" type="text" name="kontak" class="form-control" value="{{ old('kontak', $siswa->kontak) }}">
                </div>
              </div>

              {{-- Alamat harus baris baru, tetap col-6 --}}
              <div class="w-100"></div>
              <div class="mb-3 col-md-6">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea id="alamat" name="alamat" class="form-control" rows="2">{{ old('alamat', $siswa->alamat) }}</textarea>
              </div>

              {{-- Data orang tua / wali --}}
              <div class="col-12 mt-2">
                <h5 class="mb-3">Data Orang Tua/Wali</h5>
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="nama_ayah">Nama Ayah</label>
                <input id="nama_ayah" type="text" name="nama_ayah" class="form-control" value="{{ old('nama_ayah', $siswa->nama_ayah ?? '') }}">
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="nama_ibu">Nama Ibu</label>
                <input id="nama_ibu" type="text" name="nama_ibu" class="form-control" value="{{ old('nama_ibu', $siswa->nama_ibu ?? '') }}">
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="pekerjaan_ayah">Pekerjaan Ayah</label>
                <input id="pekerjaan_ayah" type="text" name="pekerjaan_ayah" class="form-control" value="{{ old('pekerjaan_ayah', $siswa->pekerjaan_ayah ?? '') }}">
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="pekerjaan_ibu">Pekerjaan Ibu</label>
                <input id="pekerjaan_ibu" type="text" name="pekerjaan_ibu" class="form-control" value="{{ old('pekerjaan_ibu', $siswa->pekerjaan_ibu ?? '') }}">
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="kontak_ayah">Kontak Ayah</label>
                <div class="input-group">
                  <span class="input-group-text">+62</span>
                  <input id="kontak_ayah" type="text" name="kontak_ayah" class="form-control" value="{{ old('kontak_ayah', $siswa->kontak_ayah ?? '') }}">
                </div>
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="kontak_ibu">Kontak Ibu</label>
                <div class="input-group">
                  <span class="input-group-text">+62</span>
                  <input id="kontak_ibu" type="text" name="kontak_ibu" class="form-control" value="{{ old('kontak_ibu', $siswa->kontak_ibu ?? '') }}">
                </div>
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="nama_wali_murid">Nama Wali Murid</label>
                <input id="nama_wali_murid" type="text" name="nama_wali_murid" class="form-control" value="{{ old('nama_wali_murid', $siswa->nama_wali_murid ?? '') }}">
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="kontak_wali">Kontak Wali Murid</label>
                <div class="input-group">
                  <span class="input-group-text">+62</span>
                  <input id="kontak_wali" type="text" name="kontak_wali" class="form-control" value="{{ old('kontak_wali', $siswa->kontak_wali ?? '') }}">
                </div>
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="alamat_orangtua">Alamat Orang Tua</label>
                <textarea id="alamat_orangtua" name="alamat_orangtua" class="form-control" rows="2">{{ old('alamat_orangtua', $siswa->alamat_orangtua ?? '') }}</textarea>
              </div>

              <div class="mb-3 col-md-6">
                <label class="form-label" for="alamat_wali">Alamat Wali Murid</label>
                <textarea id="alamat_wali" name="alamat_wali" class="form-control" rows="2">{{ old('alamat_wali', $siswa->alamat_wali ?? '') }}</textarea>
              </div>

            </div>

            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-2"></i>Simpan
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

{{-- ===== MODAL CROPPER: Rasio 1:1 ===== --}}
<div class="modal fade" id="cropperModal" tabindex="-1" aria-hidden="true" style="display:none;">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crop Foto (1:1)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <img id="cropperImage" src="#" alt="Crop Area" style="max-width:100%; display:block;">
        <small class="text-muted d-block mt-2">Geser/zoom sampai pas di kotak.</small>
      </div>
      <div class="modal-footer">
        <button type="button" id="btnRotateLeft" class="btn btn-outline-secondary">Putar Kiri 90°</button>
        <button type="button" id="btnRotateRight" class="btn btn-outline-secondary">Putar Kanan 90°</button>
        <button type="button" id="btnFlip" class="btn btn-outline-secondary">Balik Horizontal</button>
        <div class="flex-grow-1"></div>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnApplyCrop">Terapkan Crop</button>
      </div>
    </div>
  </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
  /* Anti-flash: walaupun Bootstrap CSS belum keburu load, modal tetap tidak muncul */
  #cropperModal { display: none; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function () {
  // =========================
  // Cropper (Foto 1:1) setup
  // =========================
  const uploadInput   = document.getElementById('upload');        // <input type="file" hidden>
  const btnChooseFile = document.getElementById('btnChooseFile'); // "Upload photo" button
  const btnResetFile  = document.getElementById('btnResetFile');  // "Reset" button
  const croppedInput  = document.getElementById('croppedInput');  // <input type="hidden" name="cropped">
  const previewImg    = document.getElementById('photoPreview');  // preview avatar image
  const previewInitial= document.getElementById('photoInitial');  // initial avatar (span)

  const cropImgEl      = document.getElementById('cropperImage');  // <img> inside modal
  const cropperModalEl = document.getElementById('cropperModal');

  // Bootstrap modal instance (assumes Bootstrap JS is present)
  const cropperModal  = cropperModalEl ? new bootstrap.Modal(cropperModalEl, { backdrop: 'static' }) : null;

  let cropperInstance = null;
  let flippedHoriz    = false;

  // Open file chooser
  btnChooseFile?.addEventListener('click', () => uploadInput?.click());

  // Reset file & hidden data
  btnResetFile?.addEventListener('click', () => {
    if (uploadInput) uploadInput.value = null;
    if (croppedInput) croppedInput.value = '';

    // Kembalikan ke tampilan awal (inisial), kalau sebelumnya hanya hasil crop
    if (previewImg) previewImg.classList.add('d-none');
    if (previewInitial) previewInitial.classList.remove('d-none');
  });

  // When file selected → open cropper modal
  uploadInput?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Guard: size limit (2MB)
    if (file.size > 2 * 1024 * 1024) {
      alert('Ukuran file melebihi 2 MB.');
      uploadInput.value = null;
      return;
    }

    const reader = new FileReader();
    reader.onload = (evt) => {
      if (!cropImgEl) return;
      cropImgEl.src = evt.target.result;

      cropImgEl.onload = () => {
        // Destroy previous instance
        if (cropperInstance) {
          cropperInstance.destroy();
          cropperInstance = null;
        }
        flippedHoriz = false;

        // Init cropper with 1:1 ratio
        cropperInstance = new Cropper(cropImgEl, {
          aspectRatio: 1,
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 1,
          background: false,
          responsive: true,
          zoomOnWheel: true,
          movable: true,
          cropBoxMovable: true,
          cropBoxResizable: true,
          minContainerWidth: 300,
          minContainerHeight: 300
        });

        cropperModal?.show();
      };
    };
    reader.readAsDataURL(file);
  });

  // Cropper toolbar actions
  document.getElementById('btnRotateLeft')?.addEventListener('click', () => cropperInstance?.rotate(-90));
  document.getElementById('btnRotateRight')?.addEventListener('click', () => cropperInstance?.rotate(90));
  document.getElementById('btnFlip')?.addEventListener('click', () => {
    if (!cropperInstance) return;
    flippedHoriz = !flippedHoriz;
    cropperInstance.scaleX(flippedHoriz ? -1 : 1);
  });

  // Apply crop → set preview + hidden input (base64 JPEG)
  document.getElementById('btnApplyCrop')?.addEventListener('click', () => {
    if (!cropperInstance) return;

    const canvas = cropperInstance.getCroppedCanvas({
      width: 600,
      height: 600,
      imageSmoothingEnabled: true,
      imageSmoothingQuality: 'high',
    });

    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);

    if (previewImg) {
      previewImg.src = dataUrl;
      previewImg.classList.remove('d-none');
    }
    if (previewInitial) previewInitial.classList.add('d-none');
    if (croppedInput) croppedInput.value = dataUrl;

    cropperModal?.hide();
  });

})();
</script>
@endpush
