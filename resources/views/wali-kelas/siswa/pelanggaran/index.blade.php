@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <a href="{{ route('siswa.index') }}">Kelas Binaan</a> /
  <a href="{{ route('siswa.show', $siswa->id) }}">{{ $siswa->nama_lengkap }}</a> /
  <span class="text-muted fw-light">{{ $title }}</span>
</h4>
{{-- Tabs --}}
<ul class="nav nav-pills flex-column flex-md-row mb-4">
  <li class="nav-item">
    <a class="nav-link" href="{{ route('siswa.edit', $siswa->id) }}">
      <i class="bx bx-user me-1"></i> Profil
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link active" href="{{ route('siswa.pelanggaran.index', $siswa->id) }}">
      <i class="bx bx-shield me-1"></i> Riwayat Pelanggaran
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="{{ route('siswa.absensi.index', $siswa->id) }}">
      <i class="bx bx-calendar-x me-1"></i> Riwayat Absensi
    </a>
  </li>
</ul>
<div class="card">
  <div class="card-header">
    <form class="row g-2 align-items-end" method="GET" action="{{ route('siswa.pelanggaran.index', $siswa->id) }}">
      <div class="col-md-3">
        <label class="form-label mb-1">Dari Tanggal</label>
        <input type="date" name="from" value="{{ $from }}" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1">Sampai Tanggal</label>
        <input type="date" name="to" value="{{ $to }}" class="form-control">
      </div>
      <div class="col-md-auto">
        <button class="btn btn-primary">Terapkan</button>
      </div>
      <div class="col-md-auto ms-auto">
        <a
          href="{{ route('siswa.pelanggaran.export', $siswa->id) }}?from={{ $from }}&to={{ $to }}"
          class="btn btn-outline-secondary"
          title="Export ke PDF"
        >
          <i class="bx bx-download me-1"></i> Export PDF
        </a>
      </div>
    </form>
  </div>

  <div class="card-body">
    <div id="list-container">
      @include('wali-kelas.siswa.pelanggaran._items', ['items'=>$items])
    </div>

    <div id="sentinel" class="text-center text-muted my-3">
      <small>Memuat data berikutnya…</small>
    </div>

    <template id="empty-template">
      <div class="alert alert-info mb-0">Belum ada data pelanggaran.</div>
    </template>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  let nextPage = {{ $items->currentPage() }} + 1;
  const lastPage = {{ $items->lastPage() }};
  const sentinel = document.getElementById('sentinel');
  const list = document.getElementById('list-container');

  // Jika tidak ada item sama sekali
  @if($items->total() === 0)
    const tpl = document.getElementById('empty-template').content.cloneNode(true);
    list.innerHTML = '';
    list.appendChild(tpl);
    sentinel.remove();
    return;
  @endif

  const params = new URLSearchParams({
    from: "{{ $from }}",
    to: "{{ $to }}"
  });

  const observer = new IntersectionObserver(async (entries) => {
    for (const entry of entries) {
      if (entry.isIntersecting) {
        if (nextPage > lastPage) {
          observer.unobserve(sentinel);
          sentinel.remove();
          return;
        }
        // Fetch halaman berikutnya
        const url = "{{ route('siswa.pelanggaran.more', $siswa->id) }}?"+params.toString()+"&page="+nextPage;
        try {
          const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
          const html = await res.text();
          const temp = document.createElement('div');
          temp.innerHTML = html.trim();
          list.append(...temp.childNodes);
          nextPage++;
          if (nextPage > lastPage) {
            observer.unobserve(sentinel);
            sentinel.remove();
          }
        } catch(e) {
          console.error(e);
        }
      }
    }
  }, { rootMargin: '200px' });

  observer.observe(sentinel);
})();
</script>
@endpush