<div class="card h-100 shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bx bx-category me-2"></i>Rekap Status – Hari Ini</h5>
    @if($auditIndex !== '#')
      <a href="{{ $auditIndex }}" class="btn btn-sm btn-outline-secondary">
        <i class="bx bx-search-alt-2 me-1"></i> Audit
      </a>
    @endif
  </div>
  <div class="card-body">
    @php
      $rekap = collect($rekapStatus ?? []);
      $order = [
        ['key'=>'hadir','label'=>'Hadir','icon'=>'bx-check-circle'],
        ['key'=>'terlambat','label'=>'Terlambat','icon'=>'bx-time'],
        ['key'=>'izin','label'=>'Izin','icon'=>'bx-edit'],
        ['key'=>'sakit','label'=>'Sakit','icon'=>'bx-first-aid'],
        ['key'=>'alpa','label'=>'Alpa','icon'=>'bx-x-circle'],
        ['key'=>'belum','label'=>'Belum Presensi','icon'=>'bx-minus-circle'],
      ];
    @endphp

    <div class="row g-3">
      @forelse ($order as $item)
        @php
          $val = (int) ($rekap[$item['key']] ?? 0);
          $clr = $statusColor[$item['key']] ?? 'secondary';
        @endphp
        <div class="col-6">
          <div class="border rounded p-3 d-flex align-items-center justify-content-between">
            <div>
              <small class="text-muted d-block">{{ $item['label'] }}</small>
              <strong class="fs-5">{{ $formatNum($val) }}</strong>
            </div>
            <span class="badge bg-label-{{ $clr }} p-2">
              <i class="bx {{ $item['icon'] }}"></i>
            </span>
          </div>
        </div>
      @empty
        <div class="col-12 text-muted">Belum ada data.</div>
      @endforelse
    </div>
  </div>
</div>
