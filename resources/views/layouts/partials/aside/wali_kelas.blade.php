<li class="menu-header small text-uppercase">
  <span class="menu-header-text">Wali Kelas</span>
</li>

{{-- Siswa Binaan --}}
<li class="menu-item {{ $rs('siswa.*') ? 'active' : '' }}">
  <a href="{{ route('siswa.index') }}" class="menu-link">
    <i class="menu-icon tf-icons bx bx-user"></i>
    <div class="text-truncate">Siswa Binaan</div>
  </a>
</li>

{{-- Absensi Siswa --}}
<li class="menu-item {{ $rs('absence.*') ? 'active open' : '' }}">
  <a href="{{ route('absence.index') }}" class="menu-link">
    <i class="menu-icon tf-icons bx bx-list-check"></i>
    <div class="text-truncate">Absensi Siswa</div>
  </a>
</li>

{{-- Kegiatan Pagi --}}
<li class="menu-item {{ $rs('kegiatan-absensi.*') ? 'active' : '' }}">
  <a href="{{ route('kegiatan-absensi.index') }}" class="menu-link">
    <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
    <div class="text-truncate">Kegiatan Pagi</div>
  </a>
</li>

{{-- Pelanggaran Siswa --}}
<li class="menu-item {{ $rs('pelanggaranSiswa.*') ? 'active' : '' }}">
  <a href="{{ route('pelanggaranSiswa.index') }}" class="menu-link">
    <i class="menu-icon tf-icons bx bx-error-circle"></i>
    <div class="text-truncate">Pelanggaran Siswa</div>
  </a>
</li>

{{-- Laporan --}}
<li class="menu-item {{ $rs('monthlyRecap', 'monthlyRecap.*', 'wali.audit.attendance.*') ? 'active open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    <i class="menu-icon tf-icons bx bx-file"></i>
    <div>Laporan</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ $rs('monthlyRecap', 'monthlyRecap.*') ? 'active' : '' }}">
      <a href="{{ route('monthlyRecap') }}" class="menu-link"><div>Rekap Absensi</div></a>
    </li>
    <li class="menu-item {{ $rs('wali.audit.attendance.*') ? 'active' : '' }}">
      <a href="{{ route('wali.audit.attendance.index') }}" class="menu-link"><div>Rekap Presensi</div></a>
    </li>
  </ul>
</li>