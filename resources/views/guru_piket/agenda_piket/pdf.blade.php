<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Agenda Piket Harian</title>

  {{-- Muat CSS: utamakan inline dari controller, fallback ke asset --}}
  @if (!empty($css))
    <style>{!! $css !!}</style>
  @else
    <link rel="stylesheet" href="{{ asset('assets/css/laporan-piket/styles.css') }}">
  @endif
</head>
<body>
  @php
    \Carbon\Carbon::setLocale('id');
    // Reindex agar ->get(0)/get(1) aman walau pluck keyed by id
    $gp = ($guruPiket instanceof \Illuminate\Support\Collection) ? $guruPiket->values() : collect($guruPiket);
  @endphp

  {{-- ==== SELURUH ISI AGENDA (ikut scale) ==== --}}
  <div class="scale-wrap">
    {{-- ==== KOP SURAT (gambar, ikut scale) ==== --}}
    <div class="kop-surat">
      @if($imageSrc)
        <img src="{{ $imageSrc }}" alt="Kop Surat SMKN 4 Tanjungpinang">
      @else
        <p class="center-text"><i>Kop surat tidak tersedia.</i></p>
      @endif
    </div>

    {{-- JUDUL & TANGGAL --}}
    <h1 class="center-text" style="margin-bottom:0;">AGENDA PIKET HARIAN (KBM)</h1>
    <h3 class="center-text">
      <strong>HARI/TANGGAL:</strong> {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d-m-Y') }}
    </h3>

    {{-- A. GURU TIDAK MELAKSANAKAN KBM --}}
    <h2>A. GURU YANG TIDAK MELAKSANAKAN KBM</h2>
    <table class="table-border" style="width:100%;">
      <colgroup>
        <col style="width:8%;">   {{-- No --}}
        <col>                     {{-- Nama Guru --}}
        <col style="width:16%;">  {{-- Status --}}
        <col style="width:36%;">  {{-- Keterangan --}}
      </colgroup>
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Guru</th>
          <th>Status</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        @forelse($absensiGuru as $i => $g)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td class="text-left">{{ $g->guru->nama_lengkap ?? '-' }}</td>
            <td style="text-transform:uppercase;">{{ $g->status }}</td>
            <td class="text-left">{{ $g->keterangan ?? '-' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="center-text"><i>Semua guru melaksanakan KBM seperti biasa.</i></td>
          </tr>
        @endforelse
      </tbody>
    </table>

    {{-- B. ABSENSI SISWA (3 kolom) --}}
    <h2>B. ABSENSI SISWA</h2>
    <table class="columns table-borderless">
      <tr>
        {{-- KOLOM X --}}
        <td>
          <table class="child-table table-border">
            <colgroup>
              <col style="width:12%;">   {{-- No --}}
              <col style="width:48%;">   {{-- Kelas --}}
              <col style="width:13%;">   {{-- Sakit --}}
              <col style="width:13%;">   {{-- Izin --}}
              <col style="width:14%;">   {{-- Alpa --}}
            </colgroup>
            <thead>
              <tr>
                <th>No</th>
                <th class="nowrap">Kelas</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpa</th>
              </tr>
            </thead>
            <tbody>
              @php $noX=1; $totSX=0; $totIX=0; $totAX=0; @endphp
              @foreach($kelas->where('tingkat', 10) as $k)
                @php
                  $sx = $absensiPerKelas[$k->id]['sakit'] ?? 0;
                  $ix = $absensiPerKelas[$k->id]['izin']  ?? 0;
                  $ax = $absensiPerKelas[$k->id]['alpa']  ?? 0;
                  $totSX += $sx; $totIX += $ix; $totAX += $ax;
                @endphp
                <tr>
                  <td>{{ $noX++ }}</td>
                  <td class="text-left nowrap">{{ $k->nama_kelas ?? $k->name ?? '-' }}</td>
                  <td>{{ $sx }}</td>
                  <td>{{ $ix }}</td>
                  <td>{{ $ax }}</td>
                </tr>
              @endforeach
              <tr>
                <td colspan="2" class="text-right"><b>Jumlah</b></td>
                <td><b>{{ $totSX }}</b></td>
                <td><b>{{ $totIX }}</b></td>
                <td><b>{{ $totAX }}</b></td>
              </tr>
            </tbody>
          </table>
        </td>

        {{-- KOLOM XI --}}
        <td>
          <table class="child-table table-border">
            <colgroup>
              <col style="width:12%;">
              <col style="width:48%;">
              <col style="width:13%;">
              <col style="width:13%;">
              <col style="width:14%;">
            </colgroup>
            <thead>
              <tr>
                <th>No</th>
                <th class="nowrap">Kelas</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpa</th>
              </tr>
            </thead>
            <tbody>
              @php $noXI=1; $totSXI=0; $totIXI=0; $totAXI=0; @endphp
              @foreach($kelas->where('tingkat', 11) as $k)
                @php
                  $sx = $absensiPerKelas[$k->id]['sakit'] ?? 0;
                  $ix = $absensiPerKelas[$k->id]['izin']  ?? 0;
                  $ax = $absensiPerKelas[$k->id]['alpa']  ?? 0;
                  $totSXI += $sx; $totIXI += $ix; $totAXI += $ax;
                @endphp
                <tr>
                  <td>{{ $noXI++ }}</td>
                  <td class="text-left nowrap">{{ $k->nama_kelas ?? $k->name ?? '-' }}</td>
                  <td>{{ $sx }}</td>
                  <td>{{ $ix }}</td>
                  <td>{{ $ax }}</td>
                </tr>
              @endforeach
              <tr>
                <td colspan="2" class="text-right"><b>Jumlah</b></td>
                <td><b>{{ $totSXI }}</b></td>
                <td><b>{{ $totIXI }}</b></td>
                <td><b>{{ $totAXI }}</b></td>
              </tr>
            </tbody>
          </table>
        </td>

        {{-- KOLOM XII --}}
        <td>
          <table class="child-table table-border">
            <colgroup>
              <col style="width:12%;">
              <col style="width:48%;">
              <col style="width:13%;">
              <col style="width:13%;">
              <col style="width:14%;">
            </colgroup>
            <thead>
              <tr>
                <th>No</th>
                <th class="nowrap">Kelas</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpa</th>
              </tr>
            </thead>
            <tbody>
              @php $noXII=1; $totSXII=0; $totIXII=0; $totAXII=0; @endphp
              @foreach($kelas->where('tingkat', 12) as $k)
                @php
                  $sx = $absensiPerKelas[$k->id]['sakit'] ?? 0;
                  $ix = $absensiPerKelas[$k->id]['izin']  ?? 0;
                  $ax = $absensiPerKelas[$k->id]['alpa']  ?? 0;
                  $totSXII += $sx; $totIXII += $ix; $totAXII += $ax;
                @endphp
                <tr>
                  <td>{{ $noXII++ }}</td>
                  <td class="text-left nowrap">{{ $k->nama_kelas ?? $k->name ?? '-' }}</td>
                  <td>{{ $sx }}</td>
                  <td>{{ $ix }}</td>
                  <td>{{ $ax }}</td>
                </tr>
              @endforeach
              <tr>
                <td colspan="2" class="text-right"><b>Jumlah</b></td>
                <td><b>{{ $totSXII }}</b></td>
                <td><b>{{ $totIXII }}</b></td>
                <td><b>{{ $totAXII }}</b></td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
    </table>

    {{-- RINGKASAN PERSENTASE --}}
    <table class="table-border">
      <thead>
        <tr>
          <th width="26%">Jumlah Siswa yang Tidak hadir</th>
          <th width="18%">Kelas X</th>
          <th width="18%">Kelas XI</th>
          <th width="18%">Kelas XII</th>
          <th width="20%">Total Persentase</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Persentase</td>
          <td>{{ number_format(($persentase['10']['sakit'] + $persentase['10']['izin'] + $persentase['10']['alpa']), 2) }}%</td>
          <td>{{ number_format(($persentase['11']['sakit'] + $persentase['11']['izin'] + $persentase['11']['alpa']), 2) }}%</td>
          <td>{{ number_format(($persentase['12']['sakit'] + $persentase['12']['izin'] + $persentase['12']['alpa']), 2) }}%</td>
          <td>{{ number_format($persentaseTotalAbsen, 2) }}%</td>
        </tr>
      </tbody>
    </table>

    {{-- C. CATATAN KEJADIAN --}}
    <h2>C. CATATAN KEJADIAN</h2>

    <p><strong>I. Kejadian Normal:</strong></p>
    <table class="table-border">
      <tbody>
        <tr>
          <td class="text-left">{{ $agendaPiket->kejadian_normal ?: '-' }}</td>
        </tr>
      </tbody>
    </table>

    <p><strong>II. Kejadian Masalah:</strong></p>
    {{-- beri class section-end agar tidak pecah sebelum tanda tangan --}}
    <table class="table-border section-end">
      <thead>
        <tr>
          <th>Uraian Kejadian / Masalah</th>
          <th>Solusi / Penanggulangan</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="text-left">{{ $agendaPiket->kejadian_masalah ?: '-' }}</td>
          <td class="text-left">{{ $agendaPiket->solusi ?: '-' }}</td>
        </tr>
      </tbody>
    </table>

    {{-- ====== TANDA TANGAN (anti pecah) ====== --}}
    <div class="signature-block no-break">

      {{-- === TANDA TANGAN: Kesiswaan & Guru Piket (3 kolom) === --}}
      <table class="signature-table">
        <colgroup>
          <col style="width:33.33%;">
          <col style="width:33.33%;">
          <col style="width:33.33%;">
        </colgroup>

        {{-- Baris jabatan --}}
        <tr>
          <td class="sig-role">KESISWAAN</td>
          <td class="sig-role">PETUGAS PIKET 1</td>
          <td class="sig-role">PETUGAS PIKET 2</td>
        </tr>

        {{-- Ruang paraf/ttd --}}
        <tr>
          <td class="sig-pad"><div class="sig-line"></div></td>
          <td class="sig-pad"><div class="sig-line"></div></td>
          <td class="sig-pad"><div class="sig-line"></div></td>
        </tr>

        {{-- Baris nama --}}
        <tr>
          <td><span class="sig-name">{{ $profilSekolah->kesiswaan->nama_lengkap ?? 'Nama Kesiswaan' }}</span></td>
          <td><span class="sig-name">{{ $gp->get(0) ?? '—' }}</span></td>
          <td><span class="sig-name">{{ $gp->get(1) ?? '—' }}</span></td>
        </tr>
      </table>

      {{-- === Mengetahui / Kepala Sekolah === --}}
      <table class="table-borderless" style="width:100%; margin-top: 4mm;">
        <tr><td colspan="3" class="center-text"><b>Mengetahui,</b></td></tr>
        <tr><td colspan="3" class="center-text"><b>Kepala Sekolah</b></td></tr>
        <tr><td colspan="3" class="signature-space"></td></tr>
        <tr>
          <td colspan="3" class="center-text">
            <u>{{ $profilSekolah->kepalaSekolah->nama_lengkap ?? 'Nama Kepala Sekolah' }}</u><br>
            {{ $profilSekolah->kepalaSekolah->nip ?? 'NIP' }}
          </td>
        </tr>
      </table>

    </div> {{-- /signature-block --}}
  </div> {{-- /scale-wrap --}}
</body>
</html>
