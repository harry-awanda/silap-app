<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <small class="text-muted d-block mb-1"><i class="bx bx-collection me-1"></i> Kelas X</small>
        <h4 class="mb-0">{{ $formatPct($persentaseAbsenKelasX ?? 0) }}</h4>
        <small class="text-muted">Izin • Sakit • Alpa</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <small class="text-muted d-block mb-1"><i class="bx bx-collection me-1"></i> Kelas XI</small>
        <h4 class="mb-0">{{ $formatPct($persentaseAbsenKelasXI ?? 0) }}</h4>
        <small class="text-muted">Izin • Sakit • Alpa</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <small class="text-muted d-block mb-1"><i class="bx bx-collection me-1"></i> Kelas XII</small>
        <h4 class="mb-0">{{ $formatPct($persentaseAbsenKelasXII ?? 0) }}</h4>
        <small class="text-muted">Izin • Sakit • Alpa</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <small class="text-muted d-block mb-1"><i class="bx bx-pie-chart-alt me-1"></i> Total Sekolah</small>
        <h4 class="mb-0">{{ $formatPct($persentaseTotalAbsen ?? 0) }}</h4>
        <small class="text-muted">Izin • Sakit • Alpa</small>
      </div>
    </div>
  </div>
</div>
