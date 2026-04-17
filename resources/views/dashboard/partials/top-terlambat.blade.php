<div class="card h-100 shadow-sm">
  <div class="card-header">
    <h5 class="mb-0"><i class="bx bx-up-arrow-alt me-2"></i>Top Kelas – Terlambat ({{ $formattedDate }})</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th style="width:60px;">#</th>
            <th>Nama Kelas</th>
            <th class="text-end" style="width:120px;">Terlambat</th>
          </tr>
        </thead>
        <tbody>
          @php $rows = collect($kelasTopTerlambat ?? []); @endphp
          @forelse ($rows as $i => $row)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td><i class="bx bx-door-open me-1 opacity-75"></i>{{ $row->nama_kelas ?? '-' }}</td>
              <td class="text-end">
                <span class="badge bg-label-warning">
                  <i class="bx bx-time me-1"></i>{{ (int)($row->terlambat_count ?? 0) }}
                </span>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-muted">Belum ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
