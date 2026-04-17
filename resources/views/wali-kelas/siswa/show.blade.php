@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('siswa.index') }}">Kelas Binaan</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>

{{-- ================== HEADER CARD HORIZONTAL (sesuai screenshot) ================== --}}
<div class="card mb-3 shadow-sm">
  <div class="card-body d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      {{-- Avatar bulat --}}
      <div class="avatar avatar-xl">
        @if($siswa->photo)
        
        <img src="{{ route('media', ['path' => $siswa->photo]) }}" class="rounded-circle img-fluid"
          style="object-fit:cover;width:64px;height:64px;">
        @else
          <span class="avatar-initial rounded-circle bg-primary text-white"
                style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
            {{ strtoupper(mb_substr($siswa->nama_lengkap,0,1,'UTF-8')) }}
          </span>
        @endif
      </div>

      <div>
        <h5 class="mb-1 text-uppercase">{{ $siswa->nama_lengkap }}</h5>
        <div class="text-muted">NIS: {{ $siswa->nis }}</div>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('siswa.pelanggaran.index', $siswa->id) }}" class="btn btn-danger">
        <i class="bx bx-error me-1"></i> Riwayat Pelanggaran
      </a>
      <a href="{{ route('siswa.edit', $siswa->id) }}" class="btn btn-outline-secondary">
        <i class="bx bx-edit-alt me-1"></i> Edit
      </a>
      
    </div>
  </div>
</div>

<div class="row">
  {{-- ================== KIRI: PROFIL SISWA (Sticky) ================== --}}
  <div class="col-lg-5">
    <div class="card mb-4 shadow-sm" style="position:sticky; top:1rem;">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Profil Siswa</h5>
      </div>

      <div class="card-body text-center">

        {{-- Identitas Grid --}}
        <div class="row text-start g-3">
          <div class="col-6">
            <div class="text-muted">Tempat, Tanggal Lahir</div>
            <div class="fw-medium">
              {{ $siswa->tempat_lahir ?: '-' }},
              {{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d M Y') : '-' }}
            </div>
          </div>
          <div class="col-6">
            <div class="text-muted">Jenis Kelamin</div>
            <div class="fw-medium">
              {{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}
            </div>
          </div>
          <div class="col-6">
            <div class="text-muted">Agama</div>
            <div class="fw-medium">{{ $siswa->agama ?: '-' }}</div>
          </div>
          <div class="col-12">
            <div class="text-muted">Alamat</div>
            <div class="fw-medium">{{ $siswa->alamat ?: '-' }}</div>
          </div>
        </div>

        {{-- Kontak --}}
        <h6 class="text-muted text-uppercase text-start mt-4">Kontak</h6>
        @php
          $kontakList = [
            ['label'=>'Siswa','nomor'=>$kontak['siswa'] ?? null, 'nama'=>$siswa->nama_lengkap],
            ['label'=>'Ayah','nomor'=>$kontak['ayah'] ?? null, 'nama'=>$siswa->nama_ayah],
            ['label'=>'Ibu','nomor'=>$kontak['ibu'] ?? null, 'nama'=>$siswa->nama_ibu],
            ['label'=>'Wali','nomor'=>$kontak['wali'] ?? null, 'nama'=>$siswa->nama_wali_murid],
          ];
        @endphp

        <ul class="list-unstyled mt-2 w-100 text-start">
          @foreach($kontakList as $k)
            <li class="py-2 d-flex align-items-center justify-content-between" style="border-bottom:1px dashed rgba(0,0,0,.08)">
              <div>
                <i class="bx bx-phone"></i>
                <span class="fw-medium mx-1">{{ $k['label'] }}</span>
                @if(!empty($k['nama']))
                  <span class="text-muted">— {{ $k['nama'] }}</span>
                @endif
                <div class="small text-muted ms-4">
                  Nomor: {{ $k['nomor'] ?: '-' }}
                </div>
              </div>
              <div class="d-flex gap-1">
                @if(!empty($k['nomor']))
                  <button type="button" class="btn btn-xs btn-outline-secondary" onclick="copyText('{{ $k['nomor'] }}')">
                    <i class="bx bx-copy"></i>
                  </button>
                  <a class="btn btn-xs btn-outline-success"
                     href="https://wa.me/{{ preg_replace('/\D/','',$k['nomor']) }}"
                     target="_blank" rel="noopener" title="Chat WA">
                    WA
                  </a>
                @endif
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>

  {{-- ================== KANAN: TIMELINE PELANGGARAN ================== --}}
  <div class="col-lg-7">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i> Timeline Pelanggaran</h5>
      </div>
      <div class="card-body">
        <ul class="timeline ms-2">
          @forelse(($timeline ?? []) as $item)
            @php
              $warna = $item['ikon'] ?? match($item['status'] ?? '') {
                'berat' => 'danger',
                'sedang' => 'warning',
                'ringan' => 'info',
                default => 'primary'
              };
              $tanggal = isset($item['tanggal'])
                ? \Carbon\Carbon::parse($item['tanggal'])->translatedFormat('d M Y')
                : null;
              $waktu = $item['waktu'] ?? null;
            @endphp
            <li class="timeline-item timeline-item-transparent">
              <span class="timeline-point-wrapper">
                <span class="timeline-point timeline-point-{{ $warna }}"></span>
              </span>
              <div class="timeline-event {{ $loop->last ? 'pb-0' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <h6 class="mb-0">
                    {{ $item['judul'] ?? 'Pelanggaran' }}
                    @if(!empty($item['status']))
                      <span class="badge bg-{{ $warna }} ms-2 text-uppercase">{{ $item['status'] }}</span>
                    @endif
                  </h6>
                  <small class="text-muted">{{ trim(($tanggal ? $tanggal : '').' '.($waktu ? $waktu : '')) ?: '-' }}</small>
                </div>
                @if(!empty($item['ringkas']))
                  <p class="mb-2">{{ $item['ringkas'] }}</p>
                @endif
            </li>
          @empty
            <li class="timeline-item timeline-item-transparent">
              <span class="timeline-point-wrapper">
                <span class="timeline-point timeline-point-secondary"></span>
              </span>
              <div class="timeline-event pb-0">
                <h6 class="mb-0">Belum ada pelanggaran</h6>
                <p class="mb-0">Riwayat pelanggaran akan tampil di sini.</p>
              </div>
            </li>
          @endforelse
          <li class="timeline-end-indicator"><i class="bx bx-check-circle"></i></li>
        </ul>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  function copyText(text) {
    if (!text) return;
    navigator.clipboard.writeText(text)
      .then(()=> window.toastr?.success('Nomor disalin.'))
      .catch(()=> alert('Gagal menyalin nomor.'));
  }
</script>
@endpush
