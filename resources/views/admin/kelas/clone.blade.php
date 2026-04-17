@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('admin.classrooms.index') }}">Data Kelas</a> /
  <span class="text-muted fw-light">Clone Struktur Kelas</span>
</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div class="fw-semibold">Clone Struktur Kelas (Term A → Term B)</div>
    <a href="{{ route('admin.classrooms.index') }}" class="btn btn-label-secondary btn-sm">Kembali</a>
  </div>

  <div class="card-body">
    <form action="{{ route('admin.classrooms.clone.commit') }}" method="POST" class="row g-3">
      @csrf

      <div class="col-12 col-md-6">
        <label class="form-label">Term Sumber (From)</label>
        <select class="form-select" name="from_term_id" required>
          <option value="" disabled selected>— Pilih Term Sumber —</option>
          @foreach($terms as $t)
            <option value="{{ $t->id }}">
              {{ $t->label ?? ($t->year_start.'/'. $t->year_end .' - '. ucfirst($t->semester)) }}
            </option>
          @endforeach
        </select>
        <small class="text-muted">Struktur kelas pada term ini akan disalin.</small>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Term Tujuan (To)</label>
        <select class="form-select" name="to_term_id" required>
          <option value="" disabled selected>— Pilih Term Tujuan —</option>
          @foreach($terms as $t)
            <option value="{{ $t->id }}" {{ (string)$activeTermId === (string)$t->id ? 'selected' : '' }}>
              {{ $t->label ?? ($t->year_start.'/'. $t->year_end .' - '. ucfirst($t->semester)) }}
            </option>
          @endforeach
        </select>
        <small class="text-muted">Kelas akan dibuat dengan <b>term_id</b> term tujuan ini.</small>
      </div>

      <div class="col-12">
        <label class="form-label">Mode Clone</label>

        <div class="d-flex flex-column gap-2">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="mode_skip" value="skip" checked>
            <label class="form-check-label" for="mode_skip">
              <b>Skip</b> — jika nama kelas sudah ada di term tujuan, lewati (paling aman).
            </label>
          </div>

          <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="mode_upsert" value="upsert">
            <label class="form-check-label" for="mode_upsert">
              <b>Upsert</b> — jika nama kelas sudah ada di term tujuan, update tingkatnya mengikuti term sumber.
            </label>
          </div>
        </div>

        <small class="text-muted d-block mt-2">
          Catatan: yang dijadikan patokan duplikat adalah <b>nama_kelas</b> pada term tujuan.
        </small>
      </div>

      <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('admin.classrooms.index') }}" class="btn btn-label-secondary">Batal</a>
        <button class="btn btn-primary" type="submit"
          onclick="return confirm('Yakin clone struktur kelas? Pastikan Term Tujuan sudah benar.')">
          Proses Clone
        </button>
      </div>
    </form>
  </div>
</div>
@endsection