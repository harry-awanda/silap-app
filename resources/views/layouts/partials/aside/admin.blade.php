@php
  // Helper ringkas: cocokkan berdasarkan nama route (lebih stabil daripada path URL)
  $rs = fn(...$names) => request()->routeIs(...$names);

  // Flag open/active per kelompok menu
  $laporanOpen = $rs('audit.attendance.*');

  $adminDataOpen = $rs(
    'admin.terms.*',
    'admin.guru.*',
    'admin.classrooms.*',
    'admin.homeroom.*',
    'admin.siswa.*',
    'admin.pelanggaran.*'
  );

  $userMgmtOpen = $rs(
    'admin.users.*',
    'admin.user-siswa.*',
    'admin.roles.*', // ditambahkan: interface Role
  );

  $pelanggaranSiswaOpen = $rs('pelanggaranSiswa.*'); // non-admin prefix (tetap dipertahankan)
  $promosiOpen = $rs('admin.siswa.promosi.*', 'admin.siswa.move.index', 'admin.siswa.graduate.index');
@endphp

@hasanyrole('admin|superadmin')
  @role('admin')
  {{-- LAPORAN --}}
  <li class="menu-item {{ $laporanOpen ? 'active open' : '' }}">
    <a href="javascript:void(0)" class="menu-link menu-toggle">
      <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
      <div class="text-truncate">Laporan</div>
    </a>
    <ul class="menu-sub">
      <li class="menu-item {{ $rs('audit.attendance.*') ? 'active' : '' }}">
        <a href="{{ route('audit.attendance.index') }}" class="menu-link">
          <div class="text-truncate">Audit Presensi</div>
        </a>
      </li>
    </ul>
  </li>
  @endrole

  @role('superadmin')
  {{-- DATA INDUK --}}
  <li class="menu-item {{ $adminDataOpen ? 'active open' : '' }}">
    <a href="javascript:void(0)" class="menu-link menu-toggle">
      <i class="menu-icon tf-icons bx bx-box"></i>
      <div class="text-truncate">Data Induk</div>
    </a>
    <ul class="menu-sub">
      <li class="menu-item {{ $rs('admin.terms.*') ? 'active' : '' }}">
        <a href="{{ route('admin.terms.index') }}" class="menu-link">
          <div class="text-truncate">Data Periode Akademik</div>
        </a>
      </li>

      <li class="menu-item {{ $rs('admin.guru.*') ? 'active' : '' }}">
        <a href="{{ route('admin.guru.index') }}" class="menu-link">
          <div class="text-truncate">Data Guru</div>
        </a>
      </li>

      <li class="menu-item {{ $rs('admin.classrooms.*') ? 'active' : '' }}">
        <a href="{{ route('admin.classrooms.index') }}" class="menu-link">
          <div class="text-truncate">Data Kelas</div>
        </a>
      </li>

      <li class="menu-item {{ $rs('admin.homeroom.*') ? 'active' : '' }}">
        <a href="{{ route('admin.homeroom.index') }}" class="menu-link">
          <div class="text-truncate">Data Wali Kelas</div>
        </a>
      </li>

      <li class="menu-item {{ $rs('admin.siswa.*') ? 'active' : '' }}">
        <a href="{{ route('admin.siswa.index') }}" class="menu-link">
          <div class="text-truncate">Data Siswa</div>
        </a>
      </li>

      <li class="menu-item {{ $rs('admin.pelanggaran.*') ? 'active' : '' }}">
        <a href="{{ route('admin.pelanggaran.index') }}" class="menu-link">
          <div class="text-truncate">Data Pelanggaran</div>
        </a>
      </li>
    </ul>
  </li>
  @endrole

  @role('admin')
  {{-- PELANGGARAN SISWA (non-admin prefix, tetap ditampilkan jika route ada dan memang admin perlu akses) --}}
  <li class="menu-item {{ $pelanggaranSiswaOpen ? 'active' : '' }}">
    <a href="{{ route('pelanggaranSiswa.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-error-circle"></i>
      <div class="text-truncate">Pelanggaran Siswa</div>
    </a>
  </li>

  {{-- PROMOSI / KELULUSAN --}}
  <li class="menu-item {{ $promosiOpen ? 'active open' : '' }}">
    <a href="javascript:void(0)" class="menu-link menu-toggle">
      <i class="menu-icon tf-icons bx bx-transfer"></i>
      <div class="text-truncate">Promosi Siswa</div>
    </a>
    <ul class="menu-sub">
      <li class="menu-item {{ $rs('admin.siswa.move.index') || ($rs('admin.siswa.promosi.*') && request()->route('mode') === 'promote') ? 'active' : '' }}">
        <a href="{{ route('admin.siswa.move.index') }}" class="menu-link">
          <div class="text-truncate">Naik Kelas</div>
        </a>
      </li>
      <li class="menu-item {{ $rs('admin.siswa.graduate.index') || ($rs('admin.siswa.promosi.*') && request()->route('mode') === 'graduate') ? 'active' : '' }}">
        <a href="{{ route('admin.siswa.graduate.index') }}" class="menu-link">
          <div class="text-truncate">Kelulusan</div>
        </a>
      </li>
    </ul>
  </li>

  {{-- DATA PENGGUNA --}}
  <li class="menu-item {{ $userMgmtOpen ? 'active open' : '' }}">
    <a href="javascript:void(0)" class="menu-link menu-toggle">
      <i class="menu-icon tf-icons bx bx-check-shield"></i>
      <div class="text-truncate">Data Pengguna</div>
    </a>
    <ul class="menu-sub">
      <li class="menu-item {{ $rs('admin.users.*') ? 'active' : '' }}">
        <a href="{{ route('admin.users.index') }}" class="menu-link">
          <div class="text-truncate">Manajemen User</div>
        </a>
      </li>

      <li class="menu-item {{ $rs('admin.user-siswa.*') ? 'active' : '' }}">
        <a href="{{ route('admin.user-siswa.index') }}" class="menu-link">
          <div class="text-truncate">Manajemen Siswa</div>
        </a>
      </li>

      {{-- DITAMBAHKAN: Manajemen Role (RBAC role-only) --}}
      <li class="menu-item {{ $rs('admin.roles.*') ? 'active' : '' }}">
        <a href="{{ route('admin.roles.index') }}" class="menu-link">
          <div class="text-truncate">Roles</div>
        </a>
      </li>
    </ul>
  </li>

  {{-- JADWAL PIKET --}}
  <li class="menu-item {{ $rs('admin.jadwal-piket.*') ? 'active' : '' }}">
    <a href="{{ route('admin.jadwal-piket.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-calendar"></i>
      <div class="text-truncate">Jadwal Piket</div>
    </a>
  </li>
  @endrole

  @role('superadmin')
  {{-- PROFIL SEKOLAH --}}
  <li class="menu-item {{ $rs('admin.profil.*') ? 'active' : '' }}">
    <a href="{{ route('admin.profil.edit') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-store"></i>
      <div class="text-truncate">Profil Sekolah</div>
    </a>
  </li>

  {{-- UPLOADS --}}
  <li class="menu-item {{ $rs('admin.uploads.*') ? 'active' : '' }}">
    <a href="{{ route('admin.uploads.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-window-open"></i>
      <div class="text-truncate">Uploads</div>
    </a>
  </li>
  @endrole
@endhasanyrole
