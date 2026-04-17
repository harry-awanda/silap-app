@php
  use Illuminate\Support\Facades\Route;

  $pelanggaranIndexUrl = Route::has('pelanggaranSiswa.index')
    ? route('pelanggaranSiswa.index')
    : url('/pelanggaran-siswa');
@endphp

<li class="menu-item {{ request()->is('pelanggaran-siswa*') || request()->routeIs('pelanggaranSiswa.*') ? 'active' : '' }}">
  <a href="{{ $pelanggaranIndexUrl }}" class="menu-link">
    <i class="menu-icon tf-icons bx bx-error-circle"></i>
    <div class="text-truncate">Pelanggaran Siswa</div>
  </a>
</li>
