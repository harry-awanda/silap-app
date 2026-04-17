@extends('layouts.app')

@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span>Import Data Siswa</span>
</h4>

{{-- Alert Error --}}
@if(session('import_failures'))
  @php
    $fails = session('import_failures', []);
    $sum = session('import_summary');
    $limit = 30;

    $grouped = collect($fails)
      ->filter(fn($f) => isset($f['row']))
      ->groupBy('row')
      ->map(function ($items, $rowNo) {
        $items = collect($items);

        $attributes = $items->pluck('attribute')
          ->filter()
          ->unique()
          ->values()
          ->all();

        $errors = $items->pluck('errors')
          ->flatten()
          ->filter()
          ->unique()
          ->values()
          ->all();

        $values = $items->pluck('values')->first(fn($v) => !empty($v)) ?? [];

        return [
          'row'        => $rowNo,
          'attributes' => $attributes,
          'errors'     => $errors,
          'values'     => $values,
          'count'      => $items->count(),
        ];
      })
      ->sortBy(fn($g) => (int) $g['row'])
      ->values();

    $totalRowsFailed = $grouped->count();
    $shownGroups = $grouped->take($limit);
  @endphp

  <div class="alert alert-warning">
    <div class="d-flex align-items-start justify-content-between gap-2">
      <div>
        <div class="fw-bold mb-1">
          Sebagian data gagal diimpor
          <span class="badge bg-warning text-dark ms-1">{{ $totalRowsFailed }}</span>
        </div>

        @if($sum)
          <div class="small">
            Ringkasan:
            Baru <b>{{ $sum['created'] }}</b>,
            Update <b>{{ $sum['updated'] }}</b>,
            Lewati <b>{{ $sum['skipped'] }}</b>,
            Gagal <b>{{ $sum['failedCount'] }}</b>.
          </div>
        @endif
        
        @if($totalRowsFailed > $limit)
          <div class="small text-muted mt-1">
            Menampilkan <b>{{ $limit }}</b> baris bermasalah pertama dari total <b>{{ $totalRowsFailed }}</b>.
            Perbaiki file, lalu upload ulang.
          </div>
        @endif
      </div>

      <button class="btn btn-sm btn-outline-dark" type="button"
              data-bs-toggle="collapse" data-bs-target="#importFailuresAccordionWrap"
              aria-expanded="false" aria-controls="importFailuresAccordionWrap">
        <i class="bx bx-detail me-1"></i> Detail
      </button>
    </div>

    <div class="collapse mt-3" id="importFailuresAccordionWrap">

      {{-- Controls: Buka/Tutup semua --}}
      <div class="d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnOpenAllFailures">
          <i class="bx bx-expand-alt me-1"></i> Buka Semua
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCloseAllFailures">
          <i class="bx bx-collapse-alt me-1"></i> Tutup Semua
        </button>
      </div>
      
      <div class="accordion" id="importFailuresAccordion">
        @foreach($shownGroups as $i => $g)
          @php
            $row = $g['row'];
            $attrs = $g['attributes'] ?? [];
            $errors = $g['errors'] ?? [];
            $values = $g['values'] ?? [];
            $itemId = "impFailRow{$row}";
            $attrText = !empty($attrs) ? implode(', ', $attrs) : '-';
            $errPreview = !empty($errors) ? implode(', ', array_slice($errors, 0, 2)) : '';
            $moreErr = (is_countable($errors) && count($errors) > 2) ? (' +' . (count($errors) - 2) . ' lagi') : '';
          @endphp
      
          <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $itemId }}">
              <button class="accordion-button collapsed" type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#collapse-{{ $itemId }}"
                      aria-expanded="false"
                      aria-controls="collapse-{{ $itemId }}">
                <span class="me-2 badge bg-danger">Baris {{ $row }}</span>
      
                <span class="fw-semibold">
                  Kolom: {{ $attrText }}
                </span>
      
                @if($errPreview)
                  <span class="ms-2 text-muted small">
                    {{ $errPreview }}{{ $moreErr }}
                  </span>
                @endif
              </button>
            </h2>
      
            <div id="collapse-{{ $itemId }}" class="accordion-collapse collapse"
                 aria-labelledby="heading-{{ $itemId }}"
                 data-bs-parent="#importFailuresAccordion">
              <div class="accordion-body">
      
                {{-- Daftar kolom yang error --}}
                <div class="mb-3">
                  <div class="fw-semibold mb-1">Kolom bermasalah</div>
                  <div class="d-flex flex-wrap gap-1">
                    @foreach($attrs as $a)
                      <span class="badge bg-label-danger">{{ $a }}</span>
                    @endforeach
                  </div>
                </div>
      
                {{-- Pesan error --}}
                <div class="mb-2">
                  <div class="fw-semibold mb-1">Alasan</div>
                  <ul class="mb-0">
                    @foreach((array)$errors as $err)
                      <li>{{ $err }}</li>
                    @endforeach
                  </ul>
                </div>
      
                {{-- Data baris (opsional) --}}
                @if(!empty($values) && is_array($values))
                  <div class="mt-3">
                    <div class="fw-semibold mb-2">Data pada baris tersebut</div>
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered mb-0">
                        <tbody>
                          @foreach($values as $k => $v)
                            <tr>
                              <th style="width: 220px" class="bg-light">{{ $k }}</th>
                              <td>{{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                    <div class="form-text mt-1">
                      Periksa format kolom/isi data lalu upload ulang file yang sudah diperbaiki.
                    </div>
                  </div>
                @endif
      
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif

<div class="card">
  <div class="card-header">
    <span class="fw-semibold">Import Data Siswa</span>
  </div>

  <div class="card-body">

    {{-- Template Import --}}
    <div class="col-md-6 mb-4">
      <label class="form-label fw-semibold">Template Import Siswa</label>
      <div class="input-group">
        <input type="text" class="form-control bg-light" value="{{ $fileName }}" readonly>

        @if($fileUrl)
          <a href="{{ $fileUrl }}" class="btn btn-outline-primary">
            <i class="bx bx-download"></i> Download
          </a>
        @else
          <button class="btn btn-outline-secondary" disabled>
            <i class="bx bx-block"></i> No file
          </button>
        @endif
      </div>
      <div class="form-text">Gunakan template ini agar format sesuai sistem.</div>
    </div>

    {{-- Upload Form --}}
    <form action="{{ route('siswa.import.preview') }}" method="POST" enctype="multipart/form-data" class="row g-3">
      @csrf
      <div class="col-md-6">
        <label class="form-label fw-semibold">Pilih File Excel</label>
        <input type="file" name="file" class="form-control" required>
        <div class="form-text">Format .xlsx / .xls, maksimal 20MB.</div>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary">
          <i class="bx bx-file-find"></i> Upload & Preview
        </button>
      </div>
    </form>

  </div>
</div>

@endsection

  {{-- Script khusus untuk Buka/Tutup semua --}}
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const wrap = document.getElementById('importFailuresAccordionWrap');
      if (!wrap) return;

      const openAllBtn  = document.getElementById('btnOpenAllFailures');
      const closeAllBtn = document.getElementById('btnCloseAllFailures');

      const getCollapseEls = () => wrap.querySelectorAll('.accordion-collapse');

      const openAll = () => {
        getCollapseEls().forEach(el => {
          const c = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
          c.show();
        });
      };

      const closeAll = () => {
        getCollapseEls().forEach(el => {
          const c = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
          c.hide();
        });
      };

      openAllBtn?.addEventListener('click', openAll);
      closeAllBtn?.addEventListener('click', closeAll);
    });
  </script>
  @endpush
