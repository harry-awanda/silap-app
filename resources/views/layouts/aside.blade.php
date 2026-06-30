<!-- Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo ">
    <a href="{{ route('dashboard') }}" class="app-brand-link">
      <span class="app-brand-logo demo"></span>
      <span class="app-brand-text demo menu-text fw-bold ms-2">SILAP</span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto" aria-label="Toggle sidebar">
      <i class="bx bx-chevron-left bx-sm align-middle"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  @php
    use App\Models\HomeroomAssignment;
    use App\Models\AcademicTerm;

    $user = auth()->user();

    // path-based checker
    $is = function (...$patterns) {
      foreach ($patterns as $p) {
        if (Request::is($p)) return true;
      }
      return false;
    };

    // route-based checker
    $rs = fn(...$names) => request()->routeIs(...$names);

    $dashboardActive = $rs('dashboard', 'wali.dashboard.index')
      || $is('dashboard', 'wali/dashboard/*', '/');
      
    $auditActive = $rs('audit.attendance.*') || $is('audit/attendance*');

    // role helpers
    $hasRole = fn(string $role) => $user?->hasRole($role) ?? false;
    $hasAnyRole = fn(array|string $roles) => $user?->hasAnyRole($roles) ?? false;

    // Ambil term aktif
    $activeTermId = Cache::remember('active_term_id', 60, fn () =>
      AcademicTerm::where('is_active', true)->value('id')
    );

    // Cek penugasan wali kelas di term aktif
    $guruId = optional(optional($user)->guru)->id;

    $waliKelasAdaKelas = $guruId && $activeTermId
      ? HomeroomAssignment::where('guru_id', $guruId)
          ->where('term_id', $activeTermId)
          ->whereNull('ended_at')
          ->exists()
      : false;
  @endphp

  <ul class="menu-inner py-1">
    {{-- Dashboard --}}
    <!-- <li class="menu-item {{ $is('dashboard') ? 'active' : '' }}">
      <a href="{{ route('dashboard') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home-circle"></i>
        <div class="text-truncate">Dashboard</div>
      </a>
    </li> -->
    
    <li class="menu-item {{ $dashboardActive ? 'active' : '' }}">
      <a href="{{ route('dashboard') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-home-circle"></i>
        <div class="text-truncate">Dashboard</div>
      </a>
    </li>
    
    @if ($hasAnyRole(['guru_piket','guru_bk','kesiswaan']))
    <li class="menu-item {{ $auditActive ? 'active' : '' }}">
      <a href="{{ route('audit.attendance.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-search-alt-2"></i>
        <div class="text-truncate">Audit Presensi</div>
      </a>
    </li>
    @endif

    {{-- Admin --}}
    @if ($hasAnyRole(['superadmin','admin']))
      @include('layouts.partials.aside.admin', ['is' => $is, 'rs' => $rs])
    @endif

    {{-- Guru Piket / BK --}}
    @if ($hasAnyRole(['guru_piket','guru_bk']))
      @include('layouts.partials.aside.guru_piket_bk', ['is' => $is, 'rs' => $rs])
    @endif

    {{-- Kesiswaan --}}
    @if ($hasRole('kesiswaan'))
      @include('layouts.partials.aside.kesiswaan', ['is' => $is, 'rs' => $rs])
    @endif

    {{-- Siswa --}}
    @if ($hasRole('siswa'))
      @include('layouts.partials.aside.siswa', ['is' => $is, 'rs' => $rs])
    @endif

    {{-- Wali Kelas: include only when role wali_kelas & punya kelas pada term aktif --}}
    @includeWhen(
      $user?->hasRole('wali_kelas') && $waliKelasAdaKelas,
      'layouts.partials.aside.wali_kelas',
      ['is' => $is, 'rs' => $rs]
      
    )
  </ul>
</aside>
<!-- / Menu -->
