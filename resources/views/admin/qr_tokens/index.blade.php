@extends('layouts.app')

@section('content')
@include('layouts.toasts')

{{-- Breadcrumb --}}
<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">QR Token Maintenance</span>
</h4>

<div class="row g-3">

  {{-- STAT CARDS --}}
  <div class="col-md-3">
    <div class="card border-primary">
      <div class="card-body text-center">
        <div class="text-muted small">Total Token</div>
        <div class="fs-4 fw-bold">{{ $stats['total'] }}</div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-success">
      <div class="card-body text-center">
        <div class="text-muted small">Aktif</div>
        <div class="fs-4 fw-bold text-success">{{ $stats['active'] }}</div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-warning">
      <div class="card-body text-center">
        <div class="text-muted small">Expired</div>
        <div class="fs-4 fw-bold text-warning">{{ $stats['expired'] }}</div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-secondary">
      <div class="card-body text-center">
        <div class="text-muted small">Used</div>
        <div class="fs-4 fw-bold text-secondary">{{ $stats['used'] }}</div>
      </div>
    </div>
  </div>

  {{-- CLEANUP PANEL --}}
  <div class="col-12">
    <div class="card">
      <div class="card-header fw-semibold">
        <i class="bx bx-broom"></i> Cleanup QR Token
      </div>
    
      <div class="card-body">
        <form method="POST" action="{{ route('admin.qr-tokens.cleanup') }}"
          onsubmit="return confirm('Yakin ingin menjalankan cleanup token?')">
          @csrf
    
          <div class="row g-3 align-items-start">
    
            {{-- MODE --}}
            <div class="col-md-4">
              <label class="form-label">
                Mode Cleanup <span class="text-danger">*</span>
              </label>
              <select name="mode" class="form-select" required>
                <option value="">— Pilih Mode —</option>
                <option value="expired">Hapus token expired</option>
                <option value="used">Hapus token sudah digunakan</option>
                <option value="expired_or_used">Hapus expired + used</option>
                <option value="before_date">Hapus sebelum tanggal tertentu</option>
                <option value="all" class="text-danger">⚠️ Hapus SEMUA token</option>
              </select>
            </div>
    
            {{-- DATE --}}
            <div class="col-md-4">
              <label class="form-label">Sebelum Tanggal</label>
              <input
                type="date"
                name="before_date"
                class="form-control"
              >
              <div class="form-text">
                Digunakan hanya untuk mode
                <code>before_date</code>.
              </div>
            </div>
            
            {{-- ACTION --}}
            <div class="col-md-4">
              {{-- spacer agar sejajar dengan label input --}}
              <label class="form-label invisible">Action</label>
            
              <div class="d-flex align-items-center">
                <button class="btn btn-danger px-4">
                  <i class="bx bx-trash"></i> Jalankan Cleanup
                </button>
              </div>
            </div>
    
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- LATEST TOKENS --}}
  <div class="col-12">
    <div class="card">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span>
          <i class="bx bx-history"></i> 20 Token Terakhir
        </span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Token</th>
                <th>Subjek</th>
                <th>Status</th>
                <th>Expired At</th>
                <th>Used At</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              @forelse($latest as $i => $t)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>
                    <code>{{ Str::limit($t->token, 16) }}</code>
                  </td>
                  <td>
                    <span class="badge bg-label-primary">
                      {{ $t->subject_type }}
                    </span>
                    <div class="small text-muted">
                      {{ $t->subject_ref }}
                    </div>
                  </td>
                  <td>
                    @if($t->used_at)
                      <span class="badge bg-secondary">USED</span>
                    @elseif($t->expires_at < now())
                      <span class="badge bg-warning">EXPIRED</span>
                    @else
                      <span class="badge bg-success">ACTIVE</span>
                    @endif
                  </td>
                  <td>
                    {{ $t->expires_at?->format('d M Y H:i') }}
                  </td>
                  <td>
                    {{ $t->used_at?->format('d M Y H:i') ?? '-' }}
                  </td>
                  <td>
                    {{ $t->created_at?->format('d M Y H:i') }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    Belum ada data token.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection