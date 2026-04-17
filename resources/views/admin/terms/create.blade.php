@extends('layouts.app')

@section('content')
@include('layouts.toasts')
<h4 class="py-3 mb-4">
  <a href="{{route('dashboard') }}">Dashboard</a> /
  <a href="{{route('admin.terms.index') }}">{{ $title }}</a> /
  <span class="text-muted fw-light">Tambah Data</span>
</h4>
  
  <div class="card">
    <div class="col-lg-8 mx-auto">
      <div class="card-body">
        <form action="{{ route('admin.terms.store') }}" method="POST">
          @include('admin.terms._form')
        </form>
      </div>
    </div>
  </div>
  @endsection
