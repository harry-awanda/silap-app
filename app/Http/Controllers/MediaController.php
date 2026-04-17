<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
  // /media/{path} -> ambil dari disk('public')
  public function show($path)
  {
    // hardening: blok traversal
    if (str_contains($path, '..')) {
      abort(404);
    }

    // pastikan file ada
    if (!Storage::disk('public')->exists($path)) {
      abort(404);
    }

    // Deteksi MIME + caching
    $mime = Storage::disk('public')->mimeType($path) ?? 'application/octet-stream';
    return Storage::disk('public')->response($path, null, [
      'Content-Type'  => $mime,
      // cache 7 hari, sesuaikan bila perlu
      'Cache-Control' => 'public, max-age=604800, immutable',
    ]);
  }
}