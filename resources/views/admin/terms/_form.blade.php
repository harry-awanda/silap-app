@php($editing = isset($term))
@csrf

<div class="row">
  <div class="col mb-3">
    <label class="form-label">Nama *</label>
    <input type="text" name="name" class="form-control" required
    value="{{ old('name', $term->name ?? '') }}" placeholder="TP 2025/2026 – Ganjil">
  </div>
</div>

<div class="row mb-3">
  <div class="col md-4">
    <label class="form-label">Tahun Mulai *</label>
    <input type="number" name="year_start" class="form-control" required
      value="{{ old('year_start', $term->year_start ?? '') }}">
  </div>
  <div class="col md-4">
    <label class="form-label">Tahun Akhir *</label>
    <input type="number" name="year_end" class="form-control" required
    value="{{ old('year_end', $term->year_end ?? '') }}">
  </div>
  <div class="col-md-4">
    <label class="form-label">Semester *</label>
    <select name="semester" class="form-select" required>
      @foreach(['ganjil'=>'Ganjil','genap'=>'Genap'] as $v => $label)
        <option value="{{ $v }}" @selected(old('semester', $term->semester ?? '')==$v)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-6">
    <label class="form-label">Mulai</label>
    <input type="date" name="start_date" class="form-control"
      value="{{ old('start_date', optional($term->start_date ?? null)->format('Y-m-d')) }}">
  </div>
  <div class="col-md-6">
    <label class="form-label">Selesai</label>
    <input type="date" name="end_date" class="form-control"
      value="{{ old('end_date', optional($term->end_date ?? null)->format('Y-m-d')) }}">
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-6">
    <label class="form-label">Lock Attendance At</label>
    <input type="datetime-local" name="lock_attendance_at" class="form-control"
      value="{{ old('lock_attendance_at', optional($term->lock_attendance_at ?? null)->format('Y-m-d\TH:i')) }}">
  </div>
  <div class="col-md-6">
    <label class="form-label">Lock Violation At</label>
    <input type="datetime-local" name="lock_violation_at" class="form-control"
      value="{{ old('lock_violation_at', optional($term->lock_violation_at ?? null)->format('Y-m-d\TH:i')) }}">
  </div>
</div>
<hr>
<div class="d-flex justify-content-end gap-2">
  <button class="btn btn-primary">
    <i class="bx bx-save me-2"></i>
    {{ $editing ? 'Simpan Perubahan' : 'Simpan' }}
  </button>
  <a href="{{ route('admin.terms.index') }}" class="btn btn-secondary">
    <i class="bx bx-x me-1"></i>Batal
  </a>
</div>
