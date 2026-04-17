<div class="card mb-4 shadow-sm">
  <div class="card-body d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
      <span class="badge bg-label-info p-3 rounded-circle">
        <i class="bx bx-calendar-event fs-5"></i>
      </span>
      <div>
        <h5 class="mb-0">Ringkasan Presensi Sekolah</h5>
        <small class="text-muted">Tanggal: {{ $formattedDate }}</small>
      </div>
    </div>

    @if(isset($auditIndex) && $auditIndex !== '#')
      <a href="{{ $auditIndex }}" class="btn btn-sm btn-primary d-flex align-items-center ms-md-auto">
        <i class="bx bx-show-alt me-1"></i> Lihat Audit
      </a>
    @endif
  </div>
</div>
