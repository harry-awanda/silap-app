<!-- Modal Detail -->
<div class="modal fade" id="pelanggaranDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Pelanggaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="detail-loading" class="text-center my-4 d-none">Memuat...</div>

        <div id="detail-content" class="d-none">
          <div class="row g-2">
            <div class="col-md-4">
              <div class="small text-muted mb-1">Tanggal</div>
              <div id="d-tanggal" class="fw-medium">—</div>
            </div>
            <div class="col-md-8">
              <div class="small text-muted mb-1">Siswa</div>
              <div id="d-siswa" class="fw-medium">—</div>
              <div class="small text-muted">NIS/Kelas: <span id="d-nis">—</span> / <span id="d-kelas">—</span></div>
            </div>
          </div>

          <hr>

          <div class="mb-2">
            <div class="small text-muted mb-1">Daftar Pelanggaran</div>
            <div id="d-pelanggaran">—</div>
          </div>

          <div class="row g-2">
            <div class="col-md-4">
              <div class="small text-muted mb-1">Status</div>
              <div id="d-status">—</div>
            </div>
            <div class="col-md-8">
              <div class="small text-muted mb-1">Tindakan</div>
              <div id="d-tindakan">—</div>
            </div>
          </div>

          <div class="mt-3">
            <div class="small text-muted mb-1">Keterangan</div>
            <div id="d-keterangan">—</div>
          </div>

          <div class="mt-3">
            <div class="small text-muted mb-1">Catatan</div>
            <div class="row g-2">
              <div class="col-md-4">
                <div class="fw-semibold">Wali Kelas</div>
                <div id="d-cat-wk" class="text-pre-wrap">—</div>
              </div>
              <div class="col-md-4">
                <div class="fw-semibold">Kesiswaan</div>
                <div id="d-cat-ks" class="text-pre-wrap">—</div>
              </div>
              <div class="col-md-4">
                <div class="fw-semibold">Guru BK</div>
                <div id="d-cat-bk" class="text-pre-wrap">—</div>
              </div>
            </div>
          </div>

          <div class="mt-3 small text-muted">
            <span>Dibuat: <span id="d-created">—</span></span>
            <span class="ms-3">Diubah: <span id="d-updated">—</span></span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
