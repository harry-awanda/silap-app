@php
  /** @var int $id */
  $urlDetail = route('pelanggaranSiswa.show', $id);
  $urlEdit   = route('pelanggaranSiswa.edit', $id);
  $urlDel    = route('pelanggaranSiswa.destroy', $id);

  // flags yang dikirim dari controller
  $canEdit   = $canEdit   ?? false;
  $canDelete = $canDelete ?? false;
@endphp

<div class="dropdown">
  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bx bx-dots-vertical-rounded"></i>
  </button>

  <div class="dropdown-menu">
    {{-- Detail (modal) --}}
    <a class="dropdown-item btn-detail" href="#" data-url="{{ $urlDetail }}">
      <i class="bx bx-show me-1"></i> Detail
    </a>

    @if($canEdit)
      <a class="dropdown-item" href="{{ $urlEdit }}">
        <i class="bx bx-edit-alt me-1"></i> Edit
      </a>
    @endif

    @if($canDelete)
      <div class="dropdown-divider"></div>
      <form action="{{ $urlDel }}" method="POST"
            onsubmit="return confirm('Hapus data pelanggaran ini? Tindakan tidak dapat dibatalkan.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="dropdown-item text-danger">
          <i class="bx bx-trash me-1"></i> Hapus
        </button>
      </form>
    @endif
  </div>
</div>
