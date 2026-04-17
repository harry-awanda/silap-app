<template id="tpl-actions-siswa">
  @if (auth()->user()->role == 'admin')
  <div class="dropdown">
    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bx bx-dots-vertical-rounded"></i>
    </button>
    <div class="dropdown-menu">
      {{-- Edit ke halaman edit --}}
      <a class="dropdown-item" href="{{ url('admin/siswa/:id/edit') }}">
        <i class="bx bx-edit-alt me-1"></i> Edit
      </a>

      {{-- Hapus pakai form --}}
      <form action="{{ url('admin/siswa/:id') }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="dropdown-item"
          onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
          <i class="bx bx-trash me-1"></i> Hapus
        </button>
      </form>
    </div>
  </div>
  @endif
</template>