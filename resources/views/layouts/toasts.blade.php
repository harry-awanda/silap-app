@php
  $toasts = [
    'success' => ['bg' => 'bg-success', 'icon' => 'bx bx-check-circle', 'title' => 'Berhasil!'],
    'warning' => ['bg' => 'bg-warning', 'icon' => 'bx bx-error', 'title' => 'Peringatan!'],
    'error'   => ['bg' => 'bg-danger',  'icon' => 'bx bx-x-circle', 'title' => 'Terjadi Kesalahan!'],
    'warningimport' => ['bg' => 'bg-warning', 'icon' => 'bx bx-error-circle', 'title' => 'Peringatan Import!'],
    'errorimport'   => ['bg' => 'bg-danger', 'icon' => 'bx bx-error', 'title' => 'Gagal Import!'],
    'loginSuccess'  => ['bg' => 'bg-primary', 'icon' => 'bx bx-log-in-circle', 'title' => 'Login Berhasil!'],
    'loginError'    => ['bg' => 'bg-danger',  'icon' => 'bx bx-log-in', 'title' => 'Login Gagal!'],
    'logout'        => ['bg' => 'bg-primary', 'icon' => 'bx bx-log-out', 'title' => 'Logout Berhasil!'],
  ];
@endphp

@foreach ($toasts as $key => $toast) 
  @if(session($key))
    <div id="toast-{{ $key }}" class="bs-toast toast fade {{ $toast['bg'] }} position-fixed bottom-0 end-0 m-3 text-white shadow" 
      role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <i class="{{ $toast['icon'] }} me-2"></i>
        <strong class="me-auto">{{ $toast['title'] }}</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        @if($key === 'loginSuccess')
          {{ session($key) }}, {{ auth()->user()->name ?? '' }}!
        @elseif(in_array($key, ['errorimport', 'warningimport']))
          {!! nl2br(e(session($key))) !!}
        @else
          {{ session($key) }}
        @endif
      </div>
    </div>
  @endif
@endforeach

<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.bs-toast').forEach(toastEl => {
      const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
      toast.show();
    });
  });
</script>
