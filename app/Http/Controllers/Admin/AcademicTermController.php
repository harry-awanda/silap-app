<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicTermRequest;
use App\Http\Requests\UpdateAcademicTermRequest;
use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AcademicTermController extends Controller {
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request) {
    $title = 'Academic Term';
    $terms = AcademicTerm::query()
    ->orderByDesc('is_active')
    ->orderByDesc('start_date')
    ->orderByDesc('id')
    ->get();
    
    return view('admin.terms.index', compact('title','terms'));
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create() {
    $title = 'Academic Term';
    return view('admin.terms.create', compact('title'));
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(StoreAcademicTermRequest $request) {
    $data = $request->safe()->except(['unique_combo']);
    
    // Buat term baru (default is_active=false)
    $term = AcademicTerm::create($data);
    
    // Clear cache agar middleware membaca state terbaru kalau fallback ke term terbaru
    Cache::forget('active_term.v1');
    
    return redirect()
    ->route('admin.terms.index')
    ->with('success', 'Tahun ajaran & semester berhasil dibuat.');
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(AcademicTerm $term) {
    $title = 'Academic Term';
    return view('admin.terms.edit', compact('title','term'));
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(UpdateAcademicTermRequest $request, AcademicTerm $term) {
    $data = $request->safe()->except(['unique_combo']);
    
    $term->update($data);
    
    // Jika yang diupdate adalah term aktif, kita clear cache supaya label/header ikut berubah
    if ($term->is_active) {
      Cache::forget('active_term.v1');
    }
    
    return redirect()
    ->route('admin.terms.index')
    ->with('success', 'Tahun ajaran & semester berhasil diperbarui.');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(AcademicTerm $term) {
    // Larangan opsional: cegah hapus bila sedang aktif
    if ($term->is_active) {
      return back()->with('error', 'Tidak dapat menghapus term aktif. Nonaktifkan terlebih dahulu.');
    }
    
    $term->delete();
    Cache::forget('active_term.v1');
    
    return redirect()
    ->route('admin.terms.index')
    ->with('success', 'Term berhasil dihapus.');
  }
  
  /**
   * Set satu-satunya term aktif.
   * - Menonaktifkan semua term lain
   * - Mengaktifkan term ini
   * - Invalidate cache
  */
  public function activate(Request $request, AcademicTerm $term) {
    DB::transaction(function () use ($term) {
      AcademicTerm::where('is_active', true)->update(['is_active' => false]);
      $term->update(['is_active' => true]);
    });
    
    Cache::forget('active_term.v1');
    
    return redirect()
    ->route('admin.terms.index')
    ->with('success', "Term '{$term->name}' telah diaktifkan.");
  }
}
