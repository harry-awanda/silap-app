@php
  $rs = fn(...$names) => request()->routeIs(...$names);
  $user = auth()->user();
  $isAdmin = $user?->hasRole('admin') ?? false;
  $isSuperadmin = $user?->hasRole('superadmin') ?? false;

  $operasionalOpen = $rs(
    'audit.attendance.*',
    'pelanggaranSiswa.*',
    'admin.jadwal-piket.*',
    'admin.qr-tokens.*'
  );

  $akademikOpen = $rs(
    'admin.siswa.promosi.*',
    'admin.siswa.move.index',
    'admin.siswa.graduate.index'
  );

  $dataMasterSiswaOpen = $rs(
    'admin.siswa.index',
    'admin.siswa.create',
    'admin.siswa.store',
    'admin.siswa.show',
    'admin.siswa.edit',
    'admin.siswa.update',
    'admin.siswa.destroy',
    'admin.siswa.data'
  );

  $dataMasterOpen = $rs(
    'admin.terms.*',
    'admin.guru.*',
    'admin.classrooms.*',
    'admin.homeroom.*',
    'admin.pelanggaran.*'
  ) || $dataMasterSiswaOpen;

  $sistemOpen = $rs(
    'admin.users.*',
    'admin.user-siswa.*',
    'admin.roles.*',
    'admin.profil.*',
    'admin.uploads.*'
  );
@endphp

@hasanyrole('admin|superadmin')
  @if($isAdmin)
    <li class="menu-item {{ $operasionalOpen ? 'active open' : '' }}">
      <a href="javascript:void(0)" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-grid-alt"></i>
        <div class="text-truncate">Operasional</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ $rs('audit.attendance.*') ? 'active' : '' }}">
          <a href="{{ route('audit.attendance.index') }}" class="menu-link">
            <div class="text-truncate">Audit Presensi</div>
          </a>
        </li>
        <li class="menu-item {{ $rs('pelanggaranSiswa.*') ? 'active' : '' }}">
          <a href="{{ route('pelanggaranSiswa.index') }}" class="menu-link">
            <div class="text-truncate">Pelanggaran Siswa</div>
          </a>
        </li>
        <li class="menu-item {{ $rs('admin.jadwal-piket.*') ? 'active' : '' }}">
          <a href="{{ route('admin.jadwal-piket.index') }}" class="menu-link">
            <div class="text-truncate">Jadwal Piket</div>
          </a>
        </li>
        <li class="menu-item {{ $rs('admin.qr-tokens.*') ? 'active' : '' }}">
          <a href="{{ route('admin.qr-tokens.index') }}" class="menu-link">
            <div class="text-truncate">QR Token</div>
          </a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ $akademikOpen ? 'active open' : '' }}">
      <a href="javascript:void(0)" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-transfer"></i>
        <div class="text-truncate">Akademik</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ $rs('admin.siswa.move.index') || ($rs('admin.siswa.promosi.*') && request()->route('mode') === 'promote') ? 'active' : '' }}">
          <a href="{{ route('admin.siswa.move.index') }}" class="menu-link">
            <div class="text-truncate">Lanjut / Naik Kelas</div>
          </a>
        </li>
        <li class="menu-item {{ $rs('admin.siswa.graduate.index') || ($rs('admin.siswa.promosi.*') && request()->route('mode') === 'graduate') ? 'active' : '' }}">
          <a href="{{ route('admin.siswa.graduate.index') }}" class="menu-link">
            <div class="text-truncate">Kelulusan</div>
          </a>
        </li>
      </ul>
    </li>
  @endif

  @if($isSuperadmin)
    <li class="menu-item {{ $dataMasterOpen ? 'active open' : '' }}">
      <a href="javascript:void(0)" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-box"></i>
        <div class="text-truncate">Data Master</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ $rs('admin.terms.*') ? 'active' : '' }}">
          <a href="{{ route('admin.terms.index') }}" class="menu-link">
            <div class="text-truncate">Periode Akademik</div>
          </a>
        </li>
        <li class="menu-item {{ $rs('admin.guru.*') ? 'active' : '' }}">
          <a href="{{ route('admin.guru.index') }}" class="menu-link">
            <div class="text-truncate">Guru</div>
          </a>
        </li>
        <li class="menu-item {{ $rs('admin.classrooms.*') ? 'active' : '' }}">
          <a href="{{ route('admin.classrooms.index') }}" class="menu-link">
            <div class="text-truncate">Kelas</div>
          </a>
        </li>
        <li class="menu-item {{ $rs('admin.homeroom.*') ? 'active' : '' }}">
          <a href="{{ route('admin.homeroom.index') }}" class="menu-link">
            <div class="text-truncate">Wali Kelas</div>
          </a>
        </li>
        <li class="menu-item {{ $dataMasterSiswaOpen ? 'active' : '' }}">
          <a href="{{ route('admin.siswa.index') }}" class="menu-link">
            <div class="text-truncate">Siswa</div>
          </a>
        </li>
        <li class="menu-item {{ $rs('admin.pelanggaran.*') ? 'active' : '' }}">
          <a href="{{ route('admin.pelanggaran.index') }}" class="menu-link">
            <div class="text-truncate">Pelanggaran</div>
          </a>
        </li>
      </ul>
    </li>
  @endif

  @if($isAdmin || $isSuperadmin)
    <li class="menu-item {{ $sistemOpen ? 'active open' : '' }}">
      <a href="javascript:void(0)" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons bx bx-cog"></i>
        <div class="text-truncate">Sistem</div>
      </a>
      <ul class="menu-sub">
        @if($isAdmin)
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
        @endif

        <li class="menu-item {{ $rs('admin.roles.*') ? 'active' : '' }}">
          <a href="{{ route('admin.roles.index') }}" class="menu-link">
            <div class="text-truncate">Roles</div>
          </a>
        </li>

        @if($isSuperadmin)
          <li class="menu-item {{ $rs('admin.profil.*') ? 'active' : '' }}">
            <a href="{{ route('admin.profil.edit') }}" class="menu-link">
              <div class="text-truncate">Profil Sekolah</div>
            </a>
          </li>
          <li class="menu-item {{ $rs('admin.uploads.*') ? 'active' : '' }}">
            <a href="{{ route('admin.uploads.index') }}" class="menu-link">
              <div class="text-truncate">Uploads</div>
            </a>
          </li>
        @endif
      </ul>
    </li>
  @endif
@endhasanyrole