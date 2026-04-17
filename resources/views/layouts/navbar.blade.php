@php
  $termName = $activeTerm?->name ?? '-';

  $termDates = ($activeTerm && $activeTerm->start_date && $activeTerm->end_date)
    ? (\Carbon\Carbon::parse($activeTerm->start_date)->format('d/m/Y') . ' – ' . \Carbon\Carbon::parse($activeTerm->end_date)->format('d/m/Y'))
    : null;

  // Tooltip lengkap: Nama + (rentang tanggal)
  $termTooltip = $activeTerm
    ? ($termName . ($termDates ? " ({$termDates})" : ''))
    : '-';
@endphp
<!-- Navbar -->
<nav
  class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
  id="layout-navbar">
  
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="bx bx-menu bx-sm"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

    <ul class="navbar-nav flex-row align-items-center ms-auto">
      
      {{-- Desktop: teks + tooltip Sneat --}}
      <li class="nav-item me-3 d-none d-md-flex align-items-center">
        <span
          class="badge bg-label-primary"
          data-bs-toggle="tooltip"
          data-bs-placement="bottom"
          data-bs-offset="0,8"
          data-bs-custom-class="tooltip-primary"
          title="{{ $termTooltip }}"
        >
          <i class="bx bx-calendar me-1"></i>
          {{ $termName }}
        </span>
      </li>
      
      {{-- Mobile: icon + tooltip Sneat (click) --}}
      <li class="nav-item me-2 d-flex d-md-none align-items-center">
        <button
          type="button"
          class="btn btn-icon btn-sm btn-label-primary"
          data-bs-toggle="tooltip"
          data-bs-placement="bottom"
          data-bs-offset="0,8"
          data-bs-trigger="click"
          data-bs-custom-class="tooltip-primary"
          title="{{ $termTooltip }}"
          aria-label="Tahun ajaran aktif"
        >
          <i class="bx bx-calendar"></i>
        </button>
      </li>

      <!-- User Dropdown -->
      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
          <div class="avatar">
            <img src="{{ photo_url(Auth::user()) }}"
              alt="User Avatar" class="w-px-40 rounded-circle">
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
              <div class="d-flex">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar avatar-online">
                    <img src="{{ photo_url(Auth::user()) }}"
                      alt class="w-px-40 h-auto rounded-circle">
                  </div>
                </div>
                <div class="flex-grow-1">
                  <span class="fw-medium d-block">{{ auth()->user()->name }}</span>
                  <small class="text-muted">
                    @php
                      $roles = auth()->user()->getRoleNames();
                    @endphp
                    {{ $roles->isNotEmpty() ? $roles->implode(', ') : 'Tidak ada role' }}
                  </small>
                </div>
              </div>
            </a>
          </li>

          <li><div class="dropdown-divider"></div></li>

          <li>
            <form action="{{ route('logout') }}" method="POST">
              @csrf
              <button type="submit" class="dropdown-item">
                <i class="bx bx-power-off me-2"></i>Logout
              </button>
            </form>
          </li>
        </ul>
      </li>
      <!--/ User Dropdown -->

    </ul>
  </div>
</nav>
<!-- / Navbar -->

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
      return new bootstrap.Tooltip(el);
    });
  });
</script>
@endpush