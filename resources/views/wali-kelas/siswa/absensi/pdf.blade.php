<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>{{ $title }}</title>

  @if (!empty($css))
    <style>{!! $css !!}</style>
  @else
    <link rel="stylesheet" href="{{ asset('assets/css/laporan-piket/styles.css') }}">
  @endif

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

    .table-absensi {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
    }

    .table-absensi th,
    .table-absensi td {
      border: 1px solid #000;
      padding: 5px;
      text-align: left;
    }

    .table-absensi th {
      text-align: center;
      background: #f2f2f2;
    }

    .center-text {
      text-align: center;
    }

    .scale-wrap {
      margin: 0 20px;
    }
  </style>
</head>
<body>
  @php
    \Carbon\Carbon::setLocale('id');
  @endphp

  <div class="scale-wrap">
    <div class="kop-surat">
      @if(!empty($imageSrc))
        <img src="{{ $imageSrc }}" alt="Kop Surat Sekolah">
      @else
        <p class="center-text"><i>Kop surat tidak tersedia.</i></p>
      @endif
    </div>

    <h2 class="judul-laporan">{{ $title }}</h2>

    <table class="table-borderless meta">
      <tbody>
        <tr>
          <td style="width: 25%;">Nama Siswa</td>
          <td style="width: 3%;">:</td>
          <td>{{ $siswa->nama_lengkap }}</td>
        </tr>
        <tr>
          <td>NIS</td>
          <td>:</td>
          <td>{{ $siswa->nis ?? '-' }}</td>
        </tr>
        <tr>
          <td>Kelas</td>
          <td>:</td>
          <td>{{ $siswa->classroom?->nama_kelas ?? '-' }}</td>
        </tr>
        <tr>
          <td>Rentang Tanggal</td>
          <td>:</td>
          <td>{{ $from ?: '-' }} s.d. {{ $to ?: '-' }}</td>
        </tr>
        <tr>
          <td>Status</td>
          <td>:</td>
          <td>{{ $status ? ucfirst($status) : 'Semua status' }}</td>
        </tr>
      </tbody>
    </table>

    <table class="table-absensi">
      <thead>
        <tr>
          <th style="width: 8%;">No</th>
          <th style="width: 32%;">Tanggal</th>
          <th style="width: 25%;">Waktu</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $row)
          @php
            $rowStatus = strtolower($row->status ?? '');
          @endphp
          <tr>
            <td class="center-text">{{ $loop->iteration }}</td>
            <td>{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
            <td>{{ $row->time ? \Carbon\Carbon::parse($row->time)->format('H:i') : '-' }}</td>
            <td>{{ ucfirst($rowStatus ?: '-') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="center-text">
              <i>Tidak ada data absensi pada filter tersebut.</i>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</body>
</html>
