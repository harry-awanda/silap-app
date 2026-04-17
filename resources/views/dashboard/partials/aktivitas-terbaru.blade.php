<div class="card mb-4 shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="bx bx-activity me-2"></i>Aktivitas Presensi Terbaru
    </h5>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead>
          <tr>
            <th style="width:120px;">Waktu</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th style="width:140px;">Status</th>
          </tr>
        </thead>
        <tbody>
          @php $logs = collect($recentActivities ?? []); @endphp

          @forelse ($logs as $row)
            @php
              $st  = $row->status ?? 'belum';
              $clr = $statusColor[$st] ?? 'secondary';
              $statusIcon = [
                'hadir'     => 'bx-check-circle',
                'terlambat' => 'bx-time',
                'izin'      => 'bx-edit',
                'sakit'     => 'bx-first-aid',
                'alpa'      => 'bx-x-circle',
                'belum'     => 'bx-minus-circle',
              ][$st] ?? 'bx-help-circle';
            @endphp

            <tr>
              <td>
                <i class="bx bx-time-five me-1 opacity-75"></i>
                {{ $row->time ?? '-' }}
              </td>
              <td>
                <i class="bx bx-user-circle me-1 opacity-75"></i>
                {{ $row->siswa->nama_lengkap ?? '-' }}
              </td>
              <td>
                <i class="bx bx-door-open me-1 opacity-75"></i>
                {{ $row->siswa->classroom->nama_kelas ?? '-' }}
              </td>
              <td>
                <span class="badge bg-label-{{ $clr }}">
                  <i class="bx {{ $statusIcon }} me-1"></i>{{ ucfirst($st) }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-muted text-center">
                Belum ada aktivitas hari ini.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
