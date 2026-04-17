<?php

use Illuminate\Support\Facades\Route;

if (!function_exists('photo_url')) {
  function photo_url($user) {
    $default = asset('assets/img/avatars/1.png');

    if (!$user) {
      return $default;
    }

    if ($user->guru?->photo) {
      return route('media', ['path' => $user->guru->photo]);
    }

    if ($user->siswa?->photo) {
      return route('media', ['path' => $user->siswa->photo]);
    }

    if ($user->photo) {
      return route('media', ['path' => $user->photo]);
    }

    return $default;
  }
}