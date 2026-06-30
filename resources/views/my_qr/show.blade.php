@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">My QR (Assetly)</span>
</h4>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="fw-semibold">QR Peminjaman Assetly</div>

        <form method="POST" action="{{ route('my-qr.regenerate') }}"
          onsubmit="return confirm('Buat ulang QR? QR lama akan tidak berlaku.')">
          @csrf
          <button class="btn btn-sm btn-label-primary">
            <i class="bx bx-refresh"></i> Regenerate
          </button>
        </form>
      </div>

      <div class="card-body">

        {{-- Info borrower --}}
        <div class="mb-3">
          <div class="fw-semibold">{{ $display['name'] }}</div>
          <div class="text-muted">
            <span class="badge bg-label-primary">{{ $display['role'] }}</span>
            <span class="ms-2">{{ $display['ref'] }}</span>
            @if(!empty($display['org']))
              <span class="ms-2">• {{ $display['org'] }}</span>
            @endif
          </div>
        </div>

        <div class="row g-3 align-items-center">

          <div class="col-md-6">
            <div class="border rounded p-3 d-flex justify-content-center">
              <div id="qrcode"></div>
            </div>
            <div class="form-text mt-2">
              Tunjukkan QR ini kepada admin untuk discan di Assetly.
            </div>
          </div>

          <div class="col-md-6">
            <div class="alert alert-info mb-2">
              <div class="fw-semibold mb-1">Status QR</div>
              <div>
                Berlaku sampai:
                <strong>{{ $token->expires_at?->format('d M Y H:i') }}</strong>
              </div>
              <div>
                Sisa waktu:
                <strong id="countdown">—</strong>
              </div>
            </div>

            <div class="small text-muted">
              Jika QR sudah tidak bisa dipakai (expired), klik <strong>Regenerate</strong>.
            </div>

          </div>

        </div>

      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
  const token = @json($token->token);
  const expTs = new Date(@json($token->expires_at?->format('c'))).getTime();

  // render QR
  const el = document.getElementById('qrcode');
  el.innerHTML = "";
  new QRCode(el, {
    text: token,
    width: 220,
    height: 220,
    correctLevel: QRCode.CorrectLevel.M
  });

  // countdown
  const $cd = document.getElementById('countdown');

  function tick() {
    const now = Date.now();
    let diff = Math.max(0, expTs - now);

    const totalSec = Math.floor(diff / 1000);
    const m = Math.floor(totalSec / 60);
    const s = totalSec % 60;

    $cd.textContent = `${m}m ${String(s).padStart(2, '0')}s`;

    if (diff <= 0) {
      $cd.textContent = 'Expired';
    }
  }

  tick();
  setInterval(tick, 1000);
})();
</script>
@endpush