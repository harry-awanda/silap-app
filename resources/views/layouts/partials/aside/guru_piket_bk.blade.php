@php
  use Illuminate\Support\Facades\Request;

  $user = auth()->user();
  $roles = $user?->getRoleNames()?->toArray() ?? [];

  // Helper untuk route matching (lebih akurat)
  $is = fn(...$patterns) => request()->routeIs(...$patterns) || request()->is(...$patterns);
@endphp

{{-- ================= GURU BK ================= --}}
@if (in_array('guru_bk', $roles))
  <li class="menu-header small text-uppercase">
    <span class="menu-header-text">Guru Bk</span>
  </li>

  <li class="menu-item {{ $is('pelanggaranSiswa.*', 'pelanggaran-siswa*') ? 'active' : '' }}">
    <a href="{{ route('pelanggaranSiswa.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-error-circle"></i>
      <div class="text-truncate">Pelanggaran Siswa</div>
    </a>
  </li>
@endif

{{-- ================= GURU PIKET ================= --}}
@if (in_array('guru_piket', $roles))
<li class="menu-header small text-uppercase">
  <span class="menu-header-text">Guru Piket</span>
</li>

  <li class="menu-item {{ $is('agenda_piket.*', 'agenda-piket*') ? 'active' : '' }}">
    <a href="{{ route('agenda_piket.index') }}" class="menu-link">
      <i class="menu-icon tf-icons bx bx-list-check"></i>
      <div class="text-truncate">Agenda Harian</div>
    </a>
  </li>
@endif
