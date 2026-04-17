<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body d-flex align-items-center">
        <div class="me-3">
          <span class="badge bg-label-primary p-3 rounded-circle">
            <i class="bx bx-user fs-5"></i>
          </span>
        </div>
        <div>
          <h4 class="mb-0">{{ $formatNum($jumlahSiswa ?? 0) }}</h4>
          <small class="text-muted">Total Siswa</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body d-flex align-items-center">
        <div class="me-3">
          <span class="badge bg-label-success p-3 rounded-circle">
            <i class="bx bx-chalkboard fs-5"></i>
          </span>
        </div>
        <div>
          <h4 class="mb-0">{{ $formatNum($jumlahGuru ?? 0) }}</h4>
          <small class="text-muted">Total Guru</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card h-100 shadow-sm">
      <div class="card-body d-flex align-items-center">
        <div class="me-3">
          <span class="badge bg-label-warning p-3 rounded-circle">
            <i class="bx bx-buildings fs-5"></i>
          </span>
        </div>
        <div>
          <h4 class="mb-0">{{ $formatNum($jumlahKelas ?? 0) }}</h4>
          <small class="text-muted">Total Kelas</small>
        </div>
      </div>
    </div>
  </div>
</div>
