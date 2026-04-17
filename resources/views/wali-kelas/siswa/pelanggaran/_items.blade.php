@foreach($items as $item)
  <div class="border rounded p-3 mb-2">
    <div class="d-flex justify-content-between align-items-center">
      <div class="fw-semibold">
        {{ \Illuminate\Support\Carbon::parse($item->tanggal_pelanggaran)->translatedFormat('d M Y') }}
      </div>
      <div class="small text-muted">
        Status: {{ str($item->status ?? '-')->headline() }}
      </div>
    </div>
    <div class="mt-2">
      {{-- contoh menampilkan daftar pelanggaran (jika ada relasi many-to-many dataPelanggaran) --}}
      @if($item->relationLoaded('dataPelanggaran') && $item->dataPelanggaran->isNotEmpty())
        <ul class="mb-2">
          @foreach($item->dataPelanggaran as $dp)
            <li>{{ $dp->nama ?? '-' }} @if(!empty($dp->kategori)) <span class="text-muted">({{ $dp->kategori }})</span>@endif</li>
          @endforeach
        </ul>
      @endif

      @if(!empty($item->keterangan))
        <div class="small text-muted">Keterangan:</div>
        <div>{{ $item->keterangan }}</div>
      @endif
    </div>
  </div>
@endforeach