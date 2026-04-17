<li class="menu-header small text-uppercase">
  <span class="menu-header-text">Menu</span>
</li>
<li class="menu-item {{ $is('presensi') ? 'active' : '' }}">
  <a href="{{ route('presensi.form') }}" class="menu-link">
    <div>Presensi</div>
  </a>
</li>