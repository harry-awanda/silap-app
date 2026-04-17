<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>{{ $title }}</title>

  {{-- Muat CSS: utamakan inline dari controller, fallback ke asset --}}
  @if (!empty($css))
    <style>{!! $css !!}</style>
  @else
    <link rel="stylesheet" href="{{ asset('assets/css/laporan-piket/styles.css') }}">
  @endif

  {{-- Tambahan styling khusus riwayat pelanggaran --}}
  <style>
    h1, h2, h3 {
      margin: 0 0 8px;
    }

    .judul-laporan {
      text-align: center;
      margin-bottom: 4px;
      text-transform: uppercase;
      font-size: 12px;
    }

    .meta {
      margin-bottom: 12px;
      font-size: 10px;
    }

    .meta td {
      padding: 2px 4px;
      text-align: left;
    }

    .pelanggaran-item {
      border: 1px solid #000;
      padding: 6px;
      margin-bottom: 6px;
      font-size: 10px;
    }

    .pelanggaran-item .muted {
      color: #555;
      font-size: 9px;
    }

    .pelanggaran-item ul {
      margin: 2px 0 0 16px;
      padding: 0;
    }

    .pelanggaran-item li {
      text-align: left;
    }

    .no-break {
      page-break-inside: avoid;
    }

    .signature-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
    }

    .signature-space {
      height: 40px; /* ruang kosong tanda tangan */
    }

    .scale-wrap {
      margin: 0 20px; /* left & right margin */
    }
  </style>
</head>
<body>
  @php
    \Carbon\Carbon::setLocale('id');
  @endphp

  {{-- SELURUH ISI --}}
  <div class="scale-wrap">

    {{-- KOP SURAT --}}
    <div class="kop-surat">
      @if(!empty($imageSrc))
        <img src="{{ $imageSrc }}" alt="Kop Surat Sekolah">
      @else
        <p class="center-text"><i>Kop surat tidak tersedia.</i></p>
      @endif
    </div>

    {{-- JUDUL & META DATA SISWA --}}
    <h2 class="judul-laporan">{{ $title }}</h2>

    <table class="table-borderless meta">
      <tbody>
        <tr>
          <td style="width: 25%;">Nama Siswa</td>
          <td style="width: 3%;">:</td>
          <td>{{ $siswa->nama_lengkap }}</td>
        </tr>
        <tr>
          <td>Kelas</td>
          <td>:</td>
          <td>{{ $siswa->classroom?->nama_kelas ?? '-' }}</td>
        </tr>
        <tr>
          <td>Rentang Tanggal</td>
          <td>:</td>
          <td>{{ $from ?: '—' }} s.d. {{ $to ?: '—' }}</td>
        </tr>
      </tbody>
    </table>

    {{-- DAFTAR RIWAYAT PELANGGARAN --}}
    @forelse($items as $item)
      <div class="pelanggaran-item">
        <div>
          <strong>
            {{ \Carbon\Carbon::parse($item->tanggal_pelanggaran)->translatedFormat('d M Y') }}
          </strong>
        </div>
        <div class="muted">
          Status: {{ str($item->status ?? '-')->headline() }}
        </div>

        @if($item->relationLoaded('dataPelanggaran') && $item->dataPelanggaran->isNotEmpty())
          <div style="margin-top:4px;"><em>Daftar Pelanggaran:</em></div>
          <ul>
            @foreach($item->dataPelanggaran as $dp)
              <li>
                {{ $dp->nama ?? '-' }}
                @if(!empty($dp->kategori))
                  ({{ $dp->kategori }})
                @endif
              </li>
            @endforeach
          </ul>
        @endif

        @if(!empty($item->keterangan))
          <div style="margin-top:4px;">
            <em>Keterangan:</em> {{ $item->keterangan }}
          </div>
        @endif
      </div>
    @empty
      <p class="center-text">
        <i>Tidak ada data pelanggaran pada rentang tanggal tersebut.</i>
      </p>
    @endforelse

    {{-- BLOK TANDA TANGAN ORANG TUA / WALI --}}
    <div class="no-break" style="margin-top: 16px;">
      <table class="signature-table">
        <colgroup>
          <col style="width: 55%;">
          <col style="width: 45%;">
        </colgroup>

        {{-- Baris lokasi & hari/tanggal (dalam bahasa Indonesia) --}}
        <tr>
          <td></td>
          <td class="center-text">
            {{ \Carbon\Carbon::now()->translatedFormat('l, d M Y') }}<br>
            Orang Tua / Wali Siswa
          </td>
        </tr>

        {{-- Ruang tanda tangan --}}
        <tr>
          <td></td>
          <td class="signature-space"></td>
        </tr>

        {{-- Nama orang tua (isi manual) --}}
        <tr>
          <td></td>
          <td class="center-text">
            (........................................)
          </td>
        </tr>
      </table>
    </div>

  </div> {{-- /scale-wrap --}}
</body>
</html>
