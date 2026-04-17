@extends('layouts.app')
@section('content')
@include('layouts.toasts')
<!-- Content -->
<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> / 
  <span class="text-muted fw-light"> {{ $title }}</span>
</h4>

<div class="card">
  <div class="card-header">
    @if (session('temp_password'))
    <div class="alert alert-info alert-dismissible fade show d-flex justify-content-between align-items-center mb-0" role="alert">
      <div>
        <strong>Password sementara:</strong>
        <code id="tempPass">{{ session('temp_password') }}</code>
      </div>
      <div class="d-flex align-items-center">
        <button class="btn btn-sm btn-outline-secondary me-2" type="button"
          onclick="navigator.clipboard.writeText(document.getElementById('tempPass').innerText)">
          <i class="bx bx-copy me-1"></i>Copy
        </button>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>
    @endif
    {{-- Filter Role --}}
    <form method="GET" class="mt-4 w-100">
      <div class="d-flex flex-wrap gap-2 align-items-end">
        <div>
          <select name="role" class="form-select" style="min-width:220px" onchange="this.form.submit()">
            <option value="">— Semua Role —</option>
            @foreach ($allRoles as $r)
            <option value="{{ $r->name }}" {{ ($filterRole ?? '') === $r->
              name ? 'selected' : '' }}>{{ $r->name }}
            </option>
            @endforeach
          </select>
          @if(request('role'))
          <a href="{{ route('admin.users.index') }}" class="btn btn-light">Reset</a>
          @endif
        </div>
      </div>
    </form>
  </div>

  <div class="card-body">
    <div class="table-responsive table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <!-- <th class="text-center">#</th> -->
            <th style="width:70px" class="text-center">#</th>
            <th>Nama</th>
            <th>Username / Email</th>
            <th>Role</th>
            <th class="text-center">Pilihan</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $i => $u)
          <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $u->name }}</td>
            <td>
              <div>{{ $u->username }}</div>
              <div class="text-muted small">{{ $u->email ?: '—' }}</div>
            </td>
            <td>
              @forelse ($u->getRoleNames() as $r)
              <span class="badge bg-label-primary me-1">{{ $r }}</span>
              @empty
              <span class="text-muted">—</span>
              @endforelse
            </td>
            
            <td class="text-center" width="100px">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                  <!-- Tombol Reset Password -->
                  <form method="POST" action="{{ route('admin.users.reset-password.temp', $u->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="dropdown-item"
                      onclick="return confirm('Reset password dan paksa ganti saat login?')">
                      <i class="bx bx-key me-2"></i> Reset Password
                    </button>
                  </form>
                  <a href="{{ route('admin.users.edit', $u->id) }}" class="dropdown-item">
                    <i class="bx bx-edit-alt me-2"></i> Edit
                  </a>
                  <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item" onclick="return confirm('Hapus user ini?')"
                    {{ auth()->id()===$u->id ? 'disabled' : '' }}>
                      <i class="bx bx-trash me-2"></i> Hapus
                    </button>
                  </form>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted">Belum ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
<!-- / Content -->

@endsection
@push('scripts')
<script>
  // Inisialisasi DataTable
  let table = new DataTable('.datatable');

  // Validasi password create (match confirmation)
  const pwd  = document.getElementById('password-create');
  const pwd2 = document.getElementById('password_confirmation-create');
  const help = document.getElementById('passwordHelp-create');

  function syncPwdValidity() {
    if (!pwd || !pwd2) return;
    const mismatch = pwd.value && pwd2.value && pwd.value !== pwd2.value;
    if (mismatch) {
      pwd2.setCustomValidity('Password tidak cocok');
      help?.classList.remove('d-none');
    } else {
      pwd2.setCustomValidity('');
      help?.classList.add('d-none');
    }
  }

  pwd?.addEventListener('input', syncPwdValidity);
  pwd2?.addEventListener('input', syncPwdValidity);

  // === Modal Edit User ===
  const modalEditUser = document.getElementById('modalEditUser');
  modalEditUser.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    const id       = button.getAttribute('data-id');
    const name     = button.getAttribute('data-name');
    const username = button.getAttribute('data-username');
    const email    = button.getAttribute('data-email') || '';
    const rolesRaw = button.getAttribute('data-role') || ''; // contoh: "admin,guru_piket"

    // Pecah string roles menjadi array (hapus spasi)
    const roles = rolesRaw.split(',').map(r => r.trim()).filter(r => r.length > 0);

    // Target form di dalam modal
    const form = modalEditUser.querySelector('#formEditUser');
    form.action = `/admin/users/${id}`;
    form.querySelector('#editName').value = name;
    form.querySelector('#editUsername').value = username;
    form.querySelector('#editEmail').value = email;

    // Atur opsi role
    const select = form.querySelector('#editRole');
    // Reset pilihan sebelumnya
    [...select.options].forEach(opt => opt.selected = false);
    // Centang role yang cocok
    roles.forEach(r => {
      const match = [...select.options].find(opt => opt.value === r);
      if (match) match.selected = true;
    });
  });
</script>
@endpush
