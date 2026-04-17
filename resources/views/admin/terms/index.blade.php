@extends('layouts.app')
@section('content')
@include('layouts.toasts')

<h4 class="py-3 mb-4">
  <a href="{{ route('dashboard') }}">Dashboard</a> /
  <span class="text-muted fw-light">{{ $title ?? 'Tahun Ajaran & Semester' }}</span>
</h4>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div class="btn-group" role="group">
      <a href="{{ route('admin.terms.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-2"></i>Tambah Term
      </a>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive table-hover">
      <table class="table datatable">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th class="text-center">Pilihan</th>
            <th>Nama</th>
            <th>Status</th>
            <th>Rentang</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($terms as $t)
          <tr>
            <td class="text-center" width="50px">{{ $loop->index+1 }}</td>
            <td class="text-center">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="{{ route('admin.terms.edit', $t) }}">
                    <i class="bx bx-edit-alt me-1"></i> Edit
                  </a>

                  @if(!$t->is_active)
                  <form action="{{ route('admin.terms.activate', $t) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="dropdown-item"
                      onclick="return confirm('Aktifkan term ini? Term lain akan dinonaktifkan.')">
                      <i class="bx bx-check-circle me-1"></i> Aktifkan
                    </button>
                  </form>
                  <form action="{{ route('admin.terms.destroy', $t) }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="dropdown-item"
                      onclick="return confirm('Apakah Anda yakin ingin menghapus term ini?')">
                      <i class="bx bx-trash me-1"></i> Hapus
                    </button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
            <td>{{ $t->name }}</td>
            <td>
              @if($t->is_active)
                <span class="badge bg-success">Aktif</span>
              @else
                <span class="badge bg-secondary">Nonaktif</span>
              @endif
            </td>
            <td>
              @if($t->start_date || $t->end_date)
                {{ $t->start_date?->format('d M Y') }} — {{ $t->end_date?->format('d M Y') }}
              @else
                —
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  let table = new DataTable('.datatable');
</script>
@endpush
