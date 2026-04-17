@php
  $fallback = asset('assets/img/avatars/1.png');
@endphp

<style>
  .siswa-grid-img {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    border-radius: 1rem;
    display: block;
  }
</style>

<div class="card-body">
  <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
    @forelse ($siswa as $row)
    <div class="col">
      <div class="card h-100 text-center shadow-sm">
        <div class="card-body d-flex flex-column">
          <img class="siswa-grid-img mb-2"
          src="{{ $row->photo ? asset('storage/'.$row->photo) : $fallback }}"
          alt="{{ $row->nama_lengkap }}"
          loading="lazy">
          <div class="fw-semibold small lh-sm">{{ $row->nama_lengkap }}</div>
          <div class="text-muted small">{{ $row->nis }}</div>
          <div class="flex-grow-1"></div>
        </div>
        <div class="card-footer py-2">
          <a href="{{ route('siswa.show', $row->id) }}" class="btn btn-sm btn-outline-primary w-100">Detail</a>
        </div>
      </div>
    </div>
    @empty
      <div class="col-12">
        <div class="alert alert-info mb-0">Belum ada data siswa untuk ditampilkan.</div>
      </div>
    @endforelse
  </div>
</div>
