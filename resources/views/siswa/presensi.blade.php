@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

<div class="card">
  <div class="card-body">
    <p class="mb-3">
      Presensi mandiri hanya bisa dari <strong>area sekolah</strong>.
    </p>

    <div id="statusBox" class="alert alert-info">Memeriksa lokasi perangkat…</div>

    <form id="attForm" method="POST" action="{{ route('presensi.store') }}" class="d-none">
      @csrf
      <input type="hidden" name="latitude"  id="lat">
      <input type="hidden" name="longitude" id="lng">
      <input type="hidden" name="accuracy"  id="acc">
      <input type="hidden" name="user_agent" value="{{ request()->userAgent() }}" />

      <button id="btnAbsen" type="submit" class="btn btn-success">
        <i class="bx bx-check-circle me-1"></i> Absen Hadir
      </button>
    </form>

    <div id="resultBox" class="alert d-none mt-3"></div>
    <div id="errorBox"  class="alert alert-warning d-none mt-3"></div>

    <hr>
    <small class="text-muted">
      Catatan: Jika GPS bermasalah atau akurasi terlalu tinggi, minta guru piket untuk input (izin/sakit/alpa).
    </small>
  </div>
</div>

@push('scripts')
<script>
(function() {
  const statusBox = document.getElementById('statusBox');
  const errorBox  = document.getElementById('errorBox');
  const form      = document.getElementById('attForm');
  const btnAbsen  = document.getElementById('btnAbsen');
  const resultBox = document.getElementById('resultBox');
  const latEl     = document.getElementById('lat');
  const lngEl     = document.getElementById('lng');
  const accEl     = document.getElementById('acc');

  function showForm(msg) {
    statusBox.className = 'alert alert-success';
    statusBox.textContent = msg || 'Validasi lokasi lolos.';
    form.classList.remove('d-none');
    errorBox.classList.add('d-none');
  }

  function showError(msg) {
    statusBox.className = 'alert alert-danger';
    statusBox.textContent = 'Validasi gagal.';
    errorBox.classList.remove('d-none');
    errorBox.textContent = msg;
    form.classList.add('d-none');
    resultBox.classList.add('d-none');
  }

  function showResult(success, msg) {
    resultBox.className = 'alert mt-3 ' + (success ? 'alert-success' : 'alert-warning');
    resultBox.textContent = msg;
    resultBox.classList.remove('d-none');
  }

  async function precheck(lat, lng, acc) {
    try {
      const r = await fetch('{{ route('presensi.precheck') }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ latitude: lat, longitude: lng, accuracy: acc })
      });
      const data = await r.json().catch(() => ({}));
      if (r.ok && data.ok) {
        showForm(data.message || 'Siap presensi.');
      } else {
        showError((data && data.message) ? data.message : 'Gagal validasi lokasi. Pastikan berada di area sekolah.');
      }
    } catch (_e) {
      showError('Tidak dapat menghubungi server. Coba ulangi.');
    }
  }

  // Submit via fetch agar tidak pindah halaman
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    btnAbsen.disabled = true;
    resultBox.classList.add('d-none');

    try {
      const r = await fetch(form.action, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          latitude:  latEl.value,
          longitude: lngEl.value,
          accuracy:  accEl.value,
          user_agent: '{{ request()->userAgent() }}'
        })
      });
      const data = await r.json().catch(() => ({}));
      if (r.ok && data.ok) {
        showResult(true, (data.message || 'Presensi berhasil') + (data.time ? ' — ' + data.time : ''));
        btnAbsen.textContent = 'Sudah Presensi';
        btnAbsen.classList.remove('btn-success');
        btnAbsen.classList.add('btn-secondary');
        btnAbsen.disabled = true;
      } else {
        showResult(false, (data && data.message) ? data.message : 'Gagal presensi.');
        btnAbsen.disabled = false;
      }
    } catch (_e) {
      showResult(false, 'Tidak dapat menghubungi server.');
      btnAbsen.disabled = false;
    }
  });

  if (!navigator.geolocation) {
    showError('Peramban tidak mendukung GPS.');
    return;
  }

  navigator.geolocation.getCurrentPosition(function(pos) {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const acc = pos.coords.accuracy || null;

    latEl.value = lat;
    lngEl.value = lng;
    accEl.value = acc || '';

    statusBox.textContent = 'Memvalidasi lokasi ke server…';
    precheck(lat, lng, acc);

  }, function(err) {
    let msg = 'Gagal mendapatkan lokasi.';
    if (err.code === 1) msg = 'Izin lokasi ditolak. Aktifkan izin lokasi untuk presensi.';
    if (err.code === 2) msg = 'Sinyal lokasi tidak tersedia.';
    if (err.code === 3) msg = 'Timeout mendapatkan lokasi.';
    showError(msg);
  }, {
    enableHighAccuracy: true,
    timeout: 12000,
    maximumAge: 0
  });
})();
</script>
@endpush

@endsection