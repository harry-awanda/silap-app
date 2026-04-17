@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">Presensi Wajah</span>
</h4>

<div class="card">
  <div class="card-body">

    {{-- ACTION BUTTONS --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button class="btn btn-primary" id="btnStartCam">
        <i class="bx bx-camera me-1"></i> Mulai Kamera
      </button>

      <button type="button" class="btn btn-sm btn-outline-primary" id="btnRecalibrate">
        <i class="bx bx-refresh me-1"></i> Kalibrasi Ulang
      </button>

      <button class="btn btn-outline-secondary" id="btnStopCam" disabled>
        <i class="bx bx-stop-circle me-1"></i> Stop Kamera
      </button>

      <div class="vr mx-2"></div>

      <button class="btn btn-success" id="btnEnrollStart" disabled>
        <i class="bx bx-face me-1"></i> Mulai Enrollment
      </button>

      <button class="btn btn-success" id="btnEnrollSubmit" disabled>
        <i class="bx bx-check-circle me-1"></i> Submit Enrollment
      </button>

      <div class="vr mx-2"></div>

      <button class="btn btn-warning" id="btnFaceAttendance" disabled>
        <i class="bx bx-log-in-circle me-1"></i> Presensi Wajah
      </button>
    </div>

    <div class="row g-3">
      {{-- CAMERA --}}
      <div class="col-lg-6">
        <div class="ratio ratio-4x3 border rounded overflow-hidden bg-dark">
          <video
            id="video"
            autoplay
            muted
            playsinline
            style="width:100%; height:100%; object-fit:cover;">
          </video>
        </div>

        <canvas
          id="overlay"
          class="mt-2 border rounded"
          style="width:100%;">
        </canvas>
      </div>

      {{-- STATUS & INFO --}}
      <div class="col-lg-6">
        <div class="alert alert-info" id="statusBox">
          Status: siap.
        </div>

        <ul class="list-group">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>Device ID</span>
            <code id="deviceIdTxt">-</code>
          </li>

          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>Challenge</span>
            <code id="challengeTxt">-</code>
          </li>

          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>Jumlah Kedipan</span>
            <code id="blinkTxt">0</code>
          </li>

          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>Liveness Passed</span>
            <code id="liveTxt">false</code>
          </li>

          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>EAR (Eye Aspect Ratio)</span>
            <code id="earTxt">-</code>
          </li>
          
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>Face Descriptor</span>
            <code id="descTxt">-</code>
          </li>
        </ul>

        {{-- CSRF untuk fetch --}}
        <input type="hidden" id="csrf" value="{{ csrf_token() }}">
      </div>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<!-- face-api.js (SUDAH include TensorFlow.js) -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<!-- FACE UI SCRIPT -->
<script src="{{ asset('assets/js/face-ui.js') }}"></script>
@endpush
